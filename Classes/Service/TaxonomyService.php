<?php
namespace Sitegeist\Taxonomy\Service;

use Neos\Flow\Annotations as Flow;

use Neos\ContentRepository\Domain\Factory\NodeFactory;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeTemplate;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Sitegeist\Taxonomy\Package;

class TaxonomyService
{

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var NodeFactory
     */
    protected $nodeFactory;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var string
     * @Flow\InjectConfiguration(path="contentRepository.rootNodeType")
     */
    protected $rootNodeType;

    /**
     * @var NodeInterface[]
     */
    protected $taxoniomyDataRootNodes = [];

    /**
     * @param Context $context
     * @return NodeInterface
     */
    public function getRootNode(Context $context = null)
    {
        if ($context === null) {
            $context = $this->contextFactory->create();
        }

        $contextHash = md5(json_encode($context->getProperties()));

        // return memoized root-node
        if (array_key_exists($contextHash, $this->taxoniomyDataRootNodes) && $this->taxoniomyDataRootNodes[$contextHash] instanceof NodeInterface) {
            return $this->taxoniomyDataRootNodes[$contextHash];
        }

        // return existing root-node
        $taxonomyDataRootNodeData = $this->nodeDataRepository->findOneByPath('/' . Package::ROOT_NODE_NAME, $context->getWorkspace());
        if ($taxonomyDataRootNodeData !== null) {
            $this->taxoniomyDataRootNodes[$contextHash] = $this->nodeFactory->createFromNodeData($taxonomyDataRootNodeData, $context);
            return $this->taxoniomyDataRootNodes[$contextHash];;
        }

        // create root-node
        $nodeTemplate = new NodeTemplate();
        $nodeTemplate->setNodeType($this->nodeTypeManager->getNodeType($this->rootNodeType));
        $nodeTemplate->setName(Package::ROOT_NODE_NAME);

        $rootNode = $context->getRootNode();
        $this->taxoniomyDataRootNodes[$contextHash] = $rootNode->createNodeFromTemplate($nodeTemplate);

        // persist root node
        $this->taxoniomyDataRootNodes[$contextHash]->getContext()->getWorkspace();
        $this->persistenceManager->persistAll();

        return $this->taxoniomyDataRootNodes[$contextHash];;
    }
}
