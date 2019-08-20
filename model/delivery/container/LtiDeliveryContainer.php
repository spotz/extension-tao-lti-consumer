<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA
 *
 */

namespace oat\taoLtiConsumer\model\delivery\container;

use oat\taoDelivery\model\container\delivery\AbstractContainer;
use oat\taoDelivery\model\container\execution\ExecutionClientContainer;
use oat\taoDelivery\model\container\ExecutionContainer;
use oat\taoDelivery\model\execution\DeliveryExecution;
use IMSGlobal\LTI\ToolProvider\ToolConsumer;
use oat\oatbox\session\SessionService;
use oat\generis\model\OntologyAwareTrait;
use oat\tao\model\oauth\DataStore;

/**
 * Class LtiDeliveryContainer
 *
 * A delivery container to manage LTI based delivery
 */
class LtiDeliveryContainer extends AbstractContainer
{
    use OntologyAwareTrait;

    /**
     * Get the execution container to render LTI based delivery
     *
     * @param DeliveryExecution $execution
     *
     * @return ExecutionClientContainer|ExecutionContainer
     * @throws \common_exception_InvalidArgumentType
     */
    public function getExecutionContainer(DeliveryExecution $execution)
    {
        $params = $this->getRuntimeParams();
        $ltiUrl = $params['ltiPath'];

        $ltiProvider = $this->getLtiProvider($params['ltiProvider']);
        $consumerKey = $this->getConsumerKey($ltiProvider);
        $consumerSecret = $this->getConsumerSecret($ltiProvider);

        $returnUrl = _url('index', 'DeliveryServer', 'taoDelivery');

        $data = [
            'lti_message_type' => 'basic-lti-launch-request',
            'lti_version' => 'LTI-1p0',
            'resource_link_id' => $execution->getIdentifier(),
            'user_id' => $this->getServiceLocator()->get(SessionService::SERVICE_ID)->getCurrentUser()->getIdentifier(),
            'roles' => 'Learner',
            'launch_presentation_return_url' => $returnUrl,
            'lis_result_sourcedid' => $execution->getIdentifier(),
        ];
        $data = ToolConsumer::addSignature($ltiUrl, $consumerKey, $consumerSecret, $data);

        $container = new LtiExecutionContainer($execution);
        $container->setData('launchUrl', $ltiUrl);
        $container->setData('launchParams', $data);

        return $container;
    }

    /**
     * @param $ltiProvider
     * @return string
     */
    private function getConsumerKey($ltiProvider)
    {
        return (string)reset($ltiProvider[DataStore::PROPERTY_OAUTH_KEY]);
    }

    /**
     * @param $ltiProvider
     * @return string
     */
    private function getConsumerSecret($ltiProvider)
    {
        return (string)reset($ltiProvider[DataStore::PROPERTY_OAUTH_SECRET]);
    }


    /**
     * @param $providerResource
     * @return array
     * @throws \common_exception_InvalidArgumentType
     */
    private function getLtiProvider($providerResource)
    {
        $providerResource = $this->getResource($providerResource);

        return $providerResource->getPropertiesValues([
            DataStore::PROPERTY_OAUTH_KEY,
            DataStore::PROPERTY_OAUTH_SECRET,
        ]);
    }
}
