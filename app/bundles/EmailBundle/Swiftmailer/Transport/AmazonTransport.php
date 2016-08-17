<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Transport;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\DoNotContact;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\TransferException;

/**
 * Class AmazonTransport
 */
class AmazonTransport extends \Swift_SmtpTransport implements InterfaceCallbackTransport
{
    /**
     * {@inheritdoc}
     */
    public function __construct($host = 'localhost', $port = 25, $security = null)
    {
        parent::__construct($host, 587, 'tls');
        $this->setAuthMode('login');
    }

    /**
     * Returns a "transport" string to match the URL path /mailer/{transport}/callback
     *
     * @return mixed
     */
    public function getCallbackPath()
    {
        return 'amazon';
    }

    /**
     * Handle bounces & complaints from Amazon
     *
     * http://docs.aws.amazon.com/ses/latest/DeveloperGuide/best-practices-bounces-complaints.html
     *
     * @param Request       $request
     * @param MauticFactory $factory
     *
     * @return mixed
     */
    public function handleCallbackResponse(Request $request, MauticFactory $factory)
    {
        $logger = $factory->getLogger();
        $logger->debug("Receiving webhook from Amazon");

        $translator = $factory->getTranslator();

        // Data structure that Mautic expects to be returned from this callback
        $rows = array(
            DoNotContact::BOUNCED => array(
                'hashIds' => array(),
                'emails' => array()
            ),
            DoNotContact::UNSUBSCRIBED => array(
                'hashIds' => array(),
                'emails' => array()
            )
        );

        $payload = json_decode($request->getContent(), TRUE);

        if ($payload['Type'] == 'SubscriptionConfirmation') {
            // Confirm Amazon SNS subscription by calling back the SubscribeURL from the playload
            $client = new Client();

            $requestFailed = false;
            try {
                $response = $client->get($payload['SubscribeURL']);
                if ($response->getStatusCode() == 200) {
                    $logger->info('Callback to SubscribeURL from Amazon SNS successfully');
                }
                else {
                    $requestFailed = true;
                    $reason = "HTTP Code ".$response->getStatusCode().", ".$response->getBody();
                }
            }
            catch (TransferException $e) {
                $requestFailed = true;
                if ($e->hasResponse()) {
                    $reason = Psr7\str($e->getResponse());
                }
                else {
                    $reason = $e->getMessage();
                }
            }

            if ($requestFailed) {
                $logger->error("Callback to SubscribeURL from Amazon SNS failed, reason: " . $reason);
            }

        }
        elseif ($payload['Type'] == 'Notification') {

            $message = json_decode($payload['Message'], true);

            # only deal with hard bounces
            if ($message['notificationType'] == "Bounce" && $message['bounce']['bounceType'] == "Permanent") {
                // Get bounced recipients in an array
                $bouncedRecipients = $message['bounce']['bouncedRecipients'];
                foreach ($bouncedRecipients as $bouncedRecipient) {
                    $rows[DoNotContact::BOUNCED]['emails'][$bouncedRecipient['emailAddress']] = $bouncedRecipient['diagnosticCode'];
                    $logger->debug("Mark email '" . $bouncedRecipient['emailAddress'] . "' as bounced, reason: " . $bouncedRecipient['diagnosticCode']);
                }
            }
            # unsubscribe customer that complain about spam at their mail provider
            elseif ($message['notificationType'] == 'Complaint') {
                foreach ($message['complaint']['complainedRecipients'] as $complainedRecipient) {
                    $reason = null;
                    if (isset($message['complaint']['complaintFeedbackType'])) {
                        # http://docs.aws.amazon.com/ses/latest/DeveloperGuide/notification-contents.html#complaint-object
                        switch ($message['complaint']['complaintFeedbackType']) {
                            case "abuse":
                                $reason = $translator->trans("mautic.email.complaint.reason.abuse");
                                break;
                            case "fraud":
                                $reason = $translator->trans("mautic.email.complaint.reason.fraud");
                                break;
                            case "virus":
                                $reason = $translator->trans("mautic.email.complaint.reason.virus");
                                break;
                        }
                    }

                    if ($reason == null) {
                        $reason = $translator->trans("mautic.email.complaint.reason.unkown");
                    }

                    $rows[DoNotContact::UNSUBSCRIBED]['emails'][$complainedRecipient['emailAddress']] = $reason;
                    $logger->debug("Unsubscribe email '" . $complainedRecipient['emailAddress'] . "'");
                }
            }

        }

        return $rows;
    }

}
