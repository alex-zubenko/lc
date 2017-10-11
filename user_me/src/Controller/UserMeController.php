<?php
/**
 * @file
 * Contains \Drupal\user_me\Controller\UserMeController.
 */
namespace Drupal\user_me\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class UserMeController  {
    public function content()
    {
        $uid = \Drupal::currentUser()->id();
        $currentUser = \Drupal::currentUser();
        $subid = 0;
        $gender = null;
        $birth = null;
        // get subscription uid for d6 svt
        if ($uid > 0) {
            $query = \Drupal::database()->select('mc_wsubscription', 'mcw');
            $query->addField('mcw', 'subid');
            $query->condition('mcw.uid', $uid);
            $query->condition('etat', 1);
            $query->range(0, 1);
            $subid = $query->execute()->fetchField();

            $profiles = \Drupal::entityTypeManager()->getStorage('lc_user_profile_entity')->loadByProperties([
                'user_id' => $uid
            ]);
            $gender = isset($profiles[$uid]) ? $profiles[$uid]->get('sexe')->value : 0;
            $birth = isset($profiles[$uid]) && $profiles[$uid]->get('birthday')->value ? (new \DateTime($profiles[$uid]->get('birthday')->value)) : null;
            $today = new \DateTime();
        }
        return new JsonResponse(array(
            "uid" => $uid,
            "subid" => $subid,
            "username" => $currentUser->getUsername(),
            "roles" => $currentUser->getRoles(),
            "gender"=> $gender ? "M" : "F", // 1 is male
            "age"=> $birth ? $birth->diff($today)->format("%Y") : null,
            'email' => $currentUser->getEmail()
        ));
    }
}
