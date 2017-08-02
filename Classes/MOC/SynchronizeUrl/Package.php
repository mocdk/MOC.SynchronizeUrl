<?php
namespace MOC\SynchronizeUrl;

use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Utility;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\Package as BasePackage;
use Neos\Neos\Routing\Cache\RouteCacheFlusher;
use Neos\Neos\Utility\NodeUriPathSegmentGenerator;

class Package extends BasePackage
{
    /**
     * Invokes custom PHP code directly after the package manager has been initialized.
     *
     * @param Bootstrap $bootstrap The current bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();
        $newUriPathSegment = null;
        $dispatcher->connect(
            Node::class,
            'nodePropertyChanged',
            function (Node $node, $propertyName, $oldValue, $newValue) use ($bootstrap, &$newUriPathSegment) {
                if ($propertyName === 'title' && $node->getNodeType()->isOfType('Neos.Neos:Document')) {
                    if (method_exists(NodeUriPathSegmentGenerator::class, 'generateUriPathSegment')) {
                        $nodeUriPathSegmentGenerator = $bootstrap->getObjectManager()->get(NodeUriPathSegmentGenerator::class);
                        $newUriPathSegment = strtolower($nodeUriPathSegmentGenerator->generateUriPathSegment($node));
                    } else {
                        $newUriPathSegment = strtolower(Utility::renderValidNodeName($node->getProperty('title') ?: $node->getName()));
                    }
                    $node->setProperty('uriPathSegment', $newUriPathSegment);
                    $bootstrap->getObjectManager()->get(RouteCacheFlusher::class)->registerNodeChange($node);
                } elseif ($propertyName === 'uriPathSegment' && $newUriPathSegment !== null && $newValue !== $newUriPathSegment) {
                    $node->setProperty('uriPathSegment', $newUriPathSegment);
                    $newUriPathSegment = null;
                }
            });
    }
}
