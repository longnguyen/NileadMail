<?php
/**
 * Created by Kevin.
 * User: Kevin
 * Date: 5/10/16
 * Time: 18:20
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nilead\Mail\Adapter;

use Nilead\Notification\Message\MessageInterface;
use SparkPost\SparkPost;
use SparkPost\APIResponseException;
use Psr\Log\LoggerInterface;

class SparkPostAdapter extends AbstractAdapter
{
    /**
     * @var SparkPost
     */
    protected $client;

    public function __construct(SparkPost $client)
    {
        $this->client = $client;
    }

    public function send(MessageInterface $message, LoggerInterface $logger)
    {
        try {
            return $this->client->transmission->send($this->parse($message));
        } catch (\APIResponseException $e) {
            $logger->critical(sprintf("Error code: %s\r\n Error message: %s\r\n Error description: %s\r\n", $e->getAPICode(), $e->getAPIMessage(), $e->getAPIDescription()));
        } catch (\Exception $e) {
            $logger->critical(sprintf("Error code: %s\r\n Error message: %s\r\n", $e->getCode(), $e->getMessage()));
        }
    }

    protected function parse(MessageInterface $message)
    {
        return array_merge(
            array(
                'html' => $message->getBodyHtml(),
                'text' => $message->getBody(),
                'subject' => $message->getSubject(),
                'recipients' => $this->getAddresses($message),
                'customHeaders' => array('Reply-To' => $this->getSingleAddress($message->getReplyTo())),
                'trackClicks' => true
            ),
            $this->getFrom($message->getFrom())
        );
    }

    protected function getFrom($addresses)
    {
        foreach ($addresses as $key => $value) {
            if (is_numeric($key)) {
                return [
                    'from' => $value,
                ];
            } else {
                return [
                    'from' => [
                        'name' => $key,
                        'email' => $value
                    ]
                ];
            }
        };

        return [];
    }

    protected function getAddresses(MessageInterface $message)
    {
        $list = [];

        $this->_getAddresses($message->getTo(), $list);

        return $list;
    }

    protected function _getAddresses($addresses, &$list)
    {
        if (is_array($addresses)) {
            foreach ($addresses as $key => $value) {
                if (is_numeric($key)) {
                    $list[] = [
                        'address' => [
                            'email' => $value,
                        ],
                    ];
                } else {
                    $list[] = [
                        'address' => [
                            'name' => $value,
                            'email' => $key,
                        ],
                    ];
                }
            }
        }
    }
}