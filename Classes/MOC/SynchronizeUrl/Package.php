<?php
namespace MOC\SynchronizeUrl;

use TYPO3\Flow\Package\Package as BasePackage;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\Eel\FlowQuery\FlowQuery;

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
            $q = new FlowQuery(array($node));
            $q = $q->context(array('invisibleContentShown' => true, 'removedContentShown' => true, 'inaccessibleContentShown' => true));

			if ($propertyName === 'title' && $node->getNodeType()->isOfType('TYPO3.Neos:Document')) {
				if (method_exists('TYPO3\Neos\Utility\NodeUriPathSegmentGenerator', 'generateUriPathSegment')) {
					$nodeUriPathSegmentGenerator = $bootstrap->getObjectManager()->get('TYPO3\Neos\Utility\NodeUriPathSegmentGenerator');
					$newUriPathSegment = strtolower($nodeUriPathSegmentGenerator->generateUriPathSegment($node));
				} else {
					$newUriPathSegment = strtolower(\TYPO3\TYPO3CR\Utility::renderValidNodeName($node->getProperty('title') ?: $node->getName()));
				}
				
				$initialUriPathSegment = $newUriPathSegment;
            	$i = 1;
            	while ($q->siblings('[instanceof TYPO3.Neos:Document][uriPathSegment="' . $newUriPathSegment . '"]')->count() > 0) {
                	$newUriPathSegment = $initialUriPathSegment . '-' . $i++;
            	}
            	
				$node->setProperty('uriPathSegment', $newUriPathSegment);
				$bootstrap->getObjectManager()->get('TYPO3\Neos\Routing\Cache\RouteCacheFlusher')->registerNodeChange($node);
			} elseif ($propertyName === 'uriPathSegment' && $newUriPathSegment !== NULL && $newValue !== $newUriPathSegment) {
				$node->setProperty('uriPathSegment', $newUriPathSegment);
				$newUriPathSegment = NULL;
			}
		});
	}

}