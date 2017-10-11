<?php
namespace Drupal\mtc_core\Controller;

use Drupal\mtc_core\Controller\LcBaseController;
use Drupal\user\Entity\User;
use Drupal\image\Entity\ImageStyle;
use Drupal\file\Entity\File;

/*
 * !
 * @package Drupal\mtc_core\Controller
 * @class LcUserAccountController
 * @extends Drupal\Core\Controller\LcBaseController
 * @author Matthieu Beunon
 * @date 2017-01-06 17:43:05 CET
 */
class LcUserAccountController extends LcBaseController
{

    /*
     * !
     * @TODO
     * /abonnement/accueil-client
     *
     * @method home
     * @protected
     * @return [assoc]
     */
    public function home()
    {
        // remove cache
        \Drupal::service('page_cache_kill_switch')->trigger();
        // refresh subscription
        $this->cleanTunnelDataIfNeeded();
        $content = var_export($_SESSION, true);
        $user = $this->user;
        // redirect to therapy
        $lcConfig = \Drupal::service('mtc_core.config')->get('site');
        // userRoles
        $roles = \Drupal::currentUser()->getRoles();
        //refuse access to free users
        if (count($roles) == 1  && in_array('authenticated', $roles)) {
            return $this->redirect('<front>');
        }
        //redirect to new svt
        if (is_object($user) && $user->id() > $lcConfig['max_uid_old_subscribers']) {
            return $this->redirect('mtc_core.subscriber.home.site.therap');
        }
        // @todo,verify if to add roles in check
        if (\Drupal::currentUser()->isAnonymous()) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
        }
        // load data from svt
        $svtdata = \Drupal::service('mtc_core.svt_manager')->svtHomeClient();
        // build blog_post form
        $type = node_type_load("blog_post"); // replace this with the node type in which we need to display the form for
        $node = $this->entityTypeManager()
            ->getStorage('node')
            ->create(array(
            'type' => $type->id()
        ));
        $blogform = $this->entityFormBuilder()->getForm($node);
        $blogform['field_blog_tags']['#access'] = false;
        $blogform['advanced']['#access'] = false;
        unset($blogform['actions']['preview']);
        $blogform['body']['widget'][0]['summary']['#access'] = false;
        $blogform = render($blogform);
        // get last posted blog
        $blogNid = $this->entityTypeManager()
            ->getStorage('node')
            ->getQuery()
            ->condition('status', 1)
            ->condition('uid', $user->id())
            ->condition('type', 'blog_post')
            ->condition('status', 1)
            ->sort('changed', 'DESC')
            ->range(0, 1)
            ->execute();

        $lastblog = empty($blogNid) ? false : node_load(array_shift($blogNid));
        // redirect if new subscription
        $lcConfig = \Drupal::service('mtc_core.config')->get('site');
        $blogHumour = $lastblog ? $lastblog->get('field_mon_hum')->getValue() : null;
        $termHumour = null;
        if ($blogHumour !== null && isset($blogHumour[0]['target_id'])) {
            $targetId = $blogHumour[0]['target_id'];
            $termHumour = \Drupal\taxonomy\Entity\Term::load($targetId);
        }
        if ($termHumour !== null && null !== $termHumour->get('field_image')) {
            $imageFid = $termHumour->get('field_image')->getValue();
            $imageFid = $imageFid[0]['target_id'];
            $file = File::load($imageFid);
            $imageBlogUri = ImageStyle::load('thumbnail')->buildUrl($file->getFileUri());
            $lastblog->blogHumourImage = $imageBlogUri;
        }
        return $this->twigRender('mtc_core_subscriber_home', compact('content', 'user', 'svtdata', 'blogform', 'lastblog'));
    }

    /*
     * !
     * @TODO
     * /user
     *
     * @method index
     * @protected
     * @return [assoc]
     */
    public function index()
    {}

    /*
     * !
     * @TODO
     * /abonnement
     *
     * @method homeSubscription
     * @protected
     * @return [assoc]
     */
    public function homeSubscription()
    {
        if ($this->user->isAnonymous()) {
            return $this->redirect('mtc_core.ws.tunnel.offers');
        }
        if (empty($content = $this->getStoreData('subscription.summary', 900))) {
            $content = $this->getWs()->getAccountSummaryContent($this->user);
            $this->setStoreData('subscription.summary', $content);
        }
        return $this->twigRender('mtc_core_subscription_home', compact('content'));
    }

    /*!
     * @method      updatePassword
     * @protected
     * @param       str     $password
     * @param       str     $token
     * @return      str
     */
    protected function updatePassword($password, $token)
    {
        $this->getWs()->saveUserPassword($this->currentUser(), compact('password', 'token'));
        //sync password changes
        $user = User::load($this->currentUser()->id());
        $user->setPassword($password);
        $user->save();
        //relogin user to update session
        user_login_finalize($user);
        //refresh session
        $session = \Drupal::service('session');
        $session->migrate();
        return 'Modification effectuée';
    }

    /*!
     * @method      updateInfos
     * @protected
     * @return      str
     */
    protected function updateInfos($password)
    {
        $data = $this->params([
            'idPerson',
            'idContact',
            'address1',
            'city',
            'country',
            'day',
            'month',
            'year',
            'foreigntel',
            'tel1',
            'zipcode',
            'wsgender',
            'lastname',
            'firstname',
            'email',
            'confirmEmail'
        ]);
        //sync email changes if it happened
        if ($this->getWs()->saveUserInfos($this->user, $data) && $data['confirmEmail']) {
            $user = User::load($this->currentUser()->id());
            $user->setEmail($data['email']);
            $user->save();
            //relogin user to update session
            user_login_finalize($user);
            //refresh session
            $session = \Drupal::service('session');
            $session->migrate();
        }
        return 'Modification effectuée';
    }

    /*
     * !
     * @method infos
     * @protected
     * @return [assoc]
     */
    public function infos()
    {
        $response = [ 'done' => true ];
        $idUser   = $this->user->id();
        if ($this->isMethod('POST')) {
            try {
                //password form submitted
                if (($password = $this->param('password')) && (!empty($password) && $password == $this->param('confirmPassword'))) {
                    $response['msg'] = $this->updatePassword($password, $this->param('token'));
                }
                else {
                    $response['msg'] = $this->updateInfos();
                }
                
            } catch (\Exception $e) {
                $response = [ 
                    'done' => false,
                    'msg'  => $e->getMessage()
                ];
            }
        }
        try {
            if (! empty($response) || is_null($content = $this->getStoreData('user.infos', 900))) {
                $content = $this->getWs()->getUserInfosContent($this->user, $response);
                $this->setStoreData('user.infos', $content);
            }
            $passwordForm = $this->getWs()->getUserPasswordForm($idUser);
        }
        catch( \Exception $e) {
            $response['msg'] = $e->getMessage();
        }
        $profileForm = \Drupal::formBuilder()->getForm('Drupal\mtc_core\Form\EmailSendingStatusForm');

        // > do not remove & do not uncomment - need for test
        // setcookie('partnership_offer', 'axa:DAZULIS', strtotime('+14 days'), '/');
        // $content = '<a href="/axa">go to axa</a> (referer : '.$_SERVER["HTTP_REFERER"].')' . $content;
        // <
        return $this->twigRender('mtc_core_user_account_infos', compact('content', 'profileForm', 'passwordForm', 'response'));
    }

    /*
     * !
     * @method cleanTunnelDataIfNeeded
     * @private
     */
    private function cleanTunnelDataIfNeeded()
    {
        // remove payment tunnel token
        if (! is_null($token = $this->store->get('user.ws.token'))) {
            $clean = true;
            $withOrder = false;
            $this->getWs()->getTunnelData(compact('token', 'clean', 'withOrder'));
            $this->store->delete('user.ws.token');
            $this->store->delete('user.confirm.order');
            $this->store->delete('user.payment.conversion');
            // refresh subscription
            $this->getSubscription(0);
        }
    }

    /*
     *
     * @method user home profile
     *
     */
    public function homeProfile()
    {
        // remove cache
        \Drupal::service('page_cache_kill_switch')->trigger();
        $uid = $this->user->id();
        $profil = \Drupal::entityTypeManager()->getStorage('lc_user_profile_entity')->loadByProperties([
            'user_id' => $uid
        ]);
        $user = \Drupal\user\Entity\User::load($uid);
        $pictoEntity = $user->get('user_picture')->entity;
        $default_user_profile_image = isset($pictoEntity) ? $pictoEntity->url() : file_create_url('public://avatar_selection/anonyme.jpg');
        $pseudo = $user->getDisplayName();
        $user_badge = get_user_badge_images($user);
        // /if empty ,add formula
        if ($profil) {
            // render entity
            $profil = array_shift($profil);
            $profil = entity_view($profil, 'full');
        }
        return $this->twigRender('mtc_core_user_profil_infos', compact('profil', 'user_badge', 'default_user_profile_image', 'pseudo'));
    }

    public function unsubscribeConfirmation() {

      $confirmation = [
        '#theme' => 'mtc_core_unsubscribe_confirmation',
        '#confirmation' => ['body' => 'Votre demande de désabonnement a été pris en compte.'],
      ];
      return $confirmation;
    }
}
