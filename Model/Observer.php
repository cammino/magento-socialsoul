<?php class Cammino_Socialsoul_Model_Observer extends Mage_Core_Model_Abstract
{

    public function sendSocialSoul($observer) {
        try {
            if (!empty(Mage::getSingleton('core/session')->getLomadeeParam())) {

                Mage::log('-- Novo request SocialSoul --', null, 'socialsoul.log');
                //Montando a url
                $order = $observer->getEvent()->getOrder();
                $paymentType = '';
                switch($order->getPayment()->getMethodInstance()->getCode()) {
                    case 'pagarme_cc':
                        $paymentType = 'cc';
                    break;
                    case 'pagarme_bol':
                        $paymentType = 'bl';
                    break;
                    default:
                        $paymentType = 'fp';
                    break;
                }
                $url = 'https://secure.lomadee.com/v2.png?lmdaId=' . Mage::getStoreConfig('socialsoul/general/lmdaid') . '&currency=BRL&paymentType='.urlencode($paymentType).'&orderId=' . urlencode($order->getIncrementId());
                foreach ($order->getAllItems() as $item) {
                    $product = Mage::getModel('catalog/product')->load($item->getProductId());
                    $cat = 0;
                    foreach ($product->getCategoryIds() as $key=>$category_id) {
                        if ($key == 0) {
                            $cat = Mage::getModel('catalog/category')->load($category_id)->getId();
                        }
                    } 
                    $url .= '&prod='.urlencode($item->getSku()).';'.urlencode($cat).';'.urlencode(round($item->getPrice(), 2)).';'.urlencode($item->getQtyOrdered()).';'.urlencode($item->getName());
                }
                $url .= '&discount='.urlencode(round($order->getDiscountAmount() * -1, 2)).'&lmdsid='.urlencode(Mage::getSingleton('core/session')->getLomadeeParam()).'&type=cpa&lmdorig='.Mage::getSingleton('core/session')->getUtmSource().'&origin='.Mage::getSingleton('core/session')->getUtmSource();
                Mage::log('URL:::', null, 'socialsoul.log');
                Mage::log($url, null, 'socialsoul.log');

                //Fazendo o request GET
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                curl_setopt($ch, CURLOPT_ENCODING, '');
                $response = curl_exec($ch);
                if (curl_errno($ch)) {
                    throw new Exception('Error:' . curl_error($ch));
                }
                curl_close($ch);
                Mage::log('response:::::', null, 'crmebonus.log');
                Mage::log($response, null, 'crmebonus.log');
            }
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'socialsoul.log');
        }
    }

    public function setLomadeeParamFromLink($observer) {
        $lomadeeParam = $_GET['lmdsid'];
        if (!empty($lomadeeParam)) {
            Mage::getSingleton('core/session')->setLomadeeParam($lomadeeParam);
            Mage::log('setado lomadee param na sessão: ', null, 'socialsoul.log');
            Mage::log(Mage::getSingleton('core/session')->getLomadeeParam(), null, 'socialsoul.log');
        }
    }

}
