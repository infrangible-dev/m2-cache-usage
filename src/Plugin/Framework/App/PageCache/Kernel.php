<?php

declare(strict_types=1);

namespace Infrangible\CacheUsage\Plugin\Framework\App\PageCache;

use FeWeDev\Base\Arrays;
use Infrangible\CacheUsage\Model\Cache;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\PageCache\Identifier;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\PageCache\Model\Cache\Type;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Kernel
{
    /** @var Arrays */
    protected $arrayHelper;

    /** @var Cache */
    protected $cache;

    /** @var Http */
    protected $request;

    /** @var Identifier */
    protected $identifier;

    /** @var Type */
    protected $fullPageCache;

    /** @var SerializerInterface */
    protected $serializer;

    public function __construct(
        Arrays $arrayHelper,
        Cache $cache,
        Http $request,
        Identifier $identifier,
        ?Type $fullPageCache = null,
        ?SerializerInterface $serializer = null
    ) {
        $this->arrayHelper = $arrayHelper;

        $this->cache = $cache;
        $this->request = $request;
        $this->identifier = $identifier;
        $this->fullPageCache = $fullPageCache ?? ObjectManager::getInstance()->get(Type::class);
        $this->serializer = $serializer ?? ObjectManager::getInstance()->get(SerializerInterface::class);
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function beforeLoad(\Magento\Framework\App\PageCache\Kernel $subject)
    {
        $this->cache->setCacheable(false);
        $this->cache->setCached(false);
        $this->cache->setStarted(microtime(true));

        return null;
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function afterLoad(\Magento\Framework\App\PageCache\Kernel $subject, $result)
    {
        if ($result !== false) {
            $this->cache->setCacheable(true);
            $this->cache->setCached(true);

            $additionalData = $this->fullPageCache->load(
                sprintf(
                    '%s_additional',
                    $this->identifier->getValue()
                )
            );

            if ($additionalData) {
                $this->cache->setFinished(microtime(true));

                $additionalData = $this->serializer->unserialize($additionalData);

                $this->cache->setRouteName(
                    $this->arrayHelper->getValue(
                        $additionalData,
                        'route'
                    )
                );
                $this->cache->setControllerName(
                    $this->arrayHelper->getValue(
                        $additionalData,
                        'controller'
                    )
                );
                $this->cache->setActionName(
                    $this->arrayHelper->getValue(
                        $additionalData,
                        'action'
                    )
                );
                $this->cache->setPathParameters(
                    $this->arrayHelper->getValue(
                        $additionalData,
                        'path_parameters'
                    )
                );
                $this->cache->setQueryParameters(
                    $this->arrayHelper->getValue(
                        $additionalData,
                        'query_parameters'
                    )
                );
                $this->cache->setBlocksData(
                    $this->arrayHelper->getValue(
                        $additionalData,
                        'blocks'
                    )
                );
            }
        }

        return $result;
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function beforeProcess(
        \Magento\Framework\App\PageCache\Kernel $subject,
        \Magento\Framework\App\Response\Http $response
    ): array {
        if (preg_match(
            '/public.*s-maxage=(\d+)/',
            $response->getHeader('Cache-Control')->getFieldValue(),
            $matches
        )) {
            $httpResponseCode = $response->getHttpResponseCode();

            if (($httpResponseCode == 200 || $httpResponseCode == 404) &&
                ($this->request->isGet() || $this->request->isHead())) {

                $tagsHeader = $response->getHeader('X-Magento-Tags');
                $tags = $tagsHeader ? explode(
                    ',',
                    $tagsHeader->getFieldValue()
                ) : [];

                $maxAge = $matches[ 1 ];

                $serializedData = $this->serializer->serialize($this->getData());

                $this->fullPageCache->save(
                    $serializedData,
                    sprintf(
                        '%s_additional',
                        $this->identifier->getValue()
                    ),
                    $tags,
                    intval($maxAge) + 1
                );
            }
        }

        return [$response];
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function afterProcess(\Magento\Framework\App\PageCache\Kernel $subject)
    {
        $this->cache->setFinished(microtime(true));
    }

    protected function getData(): array
    {
        return [
            'route'            => $this->cache->getRouteName(),
            'controller'       => $this->cache->getControllerName(),
            'action'           => $this->cache->getActionName(),
            'path_parameters'  => $this->cache->getPathParameters(),
            'query_parameters' => $this->cache->getQueryParameters(),
            'blocks'           => $this->cache->getBlocks()
        ];
    }
}
