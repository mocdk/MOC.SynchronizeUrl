<?php
namespace MOC\SynchronizeUrl;

use TYPO3\Flow\Package\Package as BasePackage;
use TYPO3\TYPO3CR\Domain\Model\Node;

class Package extends BasePackage {

	/**
	 * Invokes custom PHP code directly after the package manager has been initialized.
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap The current bootstrap
	 * @return void
	 */
	public function boot(\TYPO3\Flow\Core\Bootstrap $bootstrap) {
		$dispatcher = $bootstrap->getSignalSlotDispatcher();
		$newUriPathSegment = NULL;
		$dispatcher->connect('TYPO3\TYPO3CR\Domain\Model\Node', 'nodePropertyChanged', function(Node $node, $propertyName, $oldValue, $newValue) use($bootstrap, &$newUriPathSegment) {
			if (method_exists('TYPO3\Neos\Utility\NodeUriPathSegmentGenerator', 'generateUriPathSegment') || method_exists('TYPO3\Neos\Utility\NodeUriPathSegmentGenerator', 'setUniqueUriPathSegment')) {
				$nodeUriPathSegmentGenerator = $bootstrap->getObjectManager()->get('TYPO3\Neos\Utility\NodeUriPathSegmentGenerator');
			}
			if ($propertyName === 'title' && $node->getNodeType()->isOfType('TYPO3.Neos:Document')) {
				if (method_exists('TYPO3\Neos\Utility\NodeUriPathSegmentGenerator', 'generateUriPathSegment')) {
					$newUriPathSegment = strtolower($nodeUriPathSegmentGenerator->generateUriPathSegment($node));
				} else {
					$newUriPathSegment = strtolower(\TYPO3\TYPO3CR\Utility::renderValidNodeName($node->getProperty('title') ?: $node->getName()));
				}
				if (method_exists('TYPO3\Neos\Utility\NodeUriPathSegmentGenerator', 'setUniqueUriPathSegment')) {
					$nodeUriPathSegmentGenerator->setUniqueUriPathSegment($node);
				} else {
					$node->setProperty('uriPathSegment', $newUriPathSegment);
				}
				$bootstrap->getObjectManager()->get('TYPO3\Neos\Routing\Cache\RouteCacheFlusher')->registerNodeChange($node);
			} elseif ($propertyName === 'uriPathSegment' && $newUriPathSegment !== NULL && $newValue !== $newUriPathSegment) {
				if (method_exists('TYPO3\Neos\Utility\NodeUriPathSegmentGenerator', 'setUniqueUriPathSegment')) {
					$nodeUriPathSegmentGenerator->setUniqueUriPathSegment($node);
				} else {
					$node->setProperty('uriPathSegment', $newUriPathSegment);
				}
				$newUriPathSegment = NULL;
			}
		});
	}

}
