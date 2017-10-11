<?php
namespace Drupal\mtc_core\Controller;

use Drupal\mtc_core\Controller\LcBaseController;
use Drupal\user\Entity\User;

class LcSubscriptionController extends LcBaseController
{

    /**
     * @method      payAnnexOffer
     * @private
     */
    private function payAnnexOffer()
    {
        $post  = $this->params(['ip', 'token', 'idOffer', 'ip']);
        $route = 'mtc_core.ws.annex.offers';
        try {
            $this->getWs()->buyAnnex($this->currentUser(), $post);
            //update cached account data because we updated the data it relies on
            $content = $this->getWs()->getAccountSummaryContent($this->user, $post['ip']);
            $this->setStoreData('subscription.summary', $content);
            // clear cache subscription
            $this->getSubscription(0);
            drupal_set_message('Merci ! Le paiement à bien été effectué. Un ticket de paiement vous a été adressé par email.');
            $route = 'mtc_core.subscription.home';
        }
        catch (\Exception $e) {
            drupal_set_message($e->getMessage(), 'error');
        }
        return $this->redirect($route);
    }

    /**
     * @method      annexOffers
     * @public
     */
    public function annexOffers() {

        $ip = \Drupal::request()->getClientIp();

        if ($this->isMethod('POST')) {
            return $this->payAnnexOffer();
        }

        $resp = $this->getWs()->get("/ws/user/{$this->currentUser()->id()}/subscription/annex-offers?ip=$ip");

        if ($resp && $resp->done) {
            $offers = $resp->data;
        }
        else {
            $offers = '';
            drupal_set_message($resp ? $resp->msg : 'Server error', 'error');
        }

        return $this->twigRender('mtc_core_annex_offers', compact('offers'));
    }
}
