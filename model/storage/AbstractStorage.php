<?php

namespace oat\taoRevision\model\storage;

use common_ext_Namespace;
use common_ext_NamespaceManager;
use common_persistence_Manager;
use common_persistence_SqlPersistence;
use core_kernel_classes_Triple as Triple;
use Doctrine\DBAL\Query\QueryBuilder;
use oat\generis\model\kernel\persistence\smoothsql\search\driver\TaoSearchDriver;
use oat\generis\model\OntologyRdfs;
use oat\oatbox\service\ConfigurableService;
use oat\taoRevision\model\Revision;
use oat\taoRevision\model\RevisionNotFound;
use oat\taoRevision\model\RevisionStorage;

class AbstractStorage extends ConfigurableService implements RevisionStorage
{
    const REVISION_TABLE_NAME = 'revision';
    const REVISION_RESOURCE = 'resource';
    const REVISION_VERSION = 'version';
    const REVISION_USER = 'user';
    const REVISION_CREATED = 'created';
    const REVISION_MESSAGE = 'message';

    //Revision Data
    const DATA_TABLE_NAME = 'revision_data';
    const DATA_RESOURCE = 'resource';
    const DATA_VERSION = 'version';
    const DATA_SUBJECT = 'subject';
    const DATA_PREDICATE = 'predicate';
    const DATA_OBJECT = 'object';
    const DATA_LANGUAGE = 'language';

    /**
     * @var common_persistence_SqlPersistence
     */
    protected $persistence;

    public function getPersistence()
    {
        if ($this->persistence === null) {
            $this->persistence = $this->getServiceLocator()
                ->get(common_persistence_Manager::SERVICE_KEY)
                ->getPersistenceById($this->getOption('persistence'));
        }
        return $this->persistence;
    }

    /**
     * @param string $resourceId
     * @param string $version
     * @param string $created
     * @param string $author
     * @param string $message
     * @param Triple[] $data
     * @return Revision
     */
    public function addRevision($resourceId, $version, $created, $author, $message, $data)
    {
        $this->getPersistence()->insert(
            self::REVISION_TABLE_NAME,
            [
                self::REVISION_RESOURCE => (string)$resourceId,
                self::REVISION_VERSION => (string)$version,
                self::REVISION_USER => (string)$author,
                self::REVISION_MESSAGE => (string)$message,
                self::REVISION_CREATED => (string)$created
            ]
        );

        $revision = new Revision($resourceId, $version, $created, $author, $message);

        if (!empty($data)) {
            $this->saveData($revision, $data);
        }
        return $revision;
    }

    /**
     *
     * @param string $resourceId
     * @param string $version
     * @return Revision
     * @throws RevisionNotFound
     */
    public function getRevision($resourceId, $version)
    {
        $queryBuilder = $this->getQueryBuilder()
            ->select('*')
            ->from(self::REVISION_TABLE_NAME)
            ->where(
                sprintf('( `%s` = ? AND `%s` = ? )', self::REVISION_RESOURCE, self::REVISION_VERSION)
            );

        $params = [$resourceId, $version];

        $variables = $this->getPersistence()->query($queryBuilder->getSQL(), $params)->fetchAll();

        if (count($variables) !== 1) {
            throw new RevisionNotFound($resourceId, $version);
        }
        $variable = reset($variables);
        return new Revision(
            $variable[self::REVISION_RESOURCE],
            $variable[self::REVISION_VERSION],
            $variable[self::REVISION_CREATED],
            $variable[self::REVISION_USER],
            $variable[self::REVISION_MESSAGE]
        );
    }

    /**
     * @param string $resourceId
     * @return Revision[]
     */
    public function getAllRevisions($resourceId)
    {

        $queryBuilder = $this->getQueryBuilder()
            ->select('*')
            ->from(self::REVISION_TABLE_NAME)
            ->where(
                sprintf(' `%s` = ? ', self::REVISION_RESOURCE)
            );

        $parameters = [
            $resourceId
        ];
        $variables = $this->getPersistence()
            ->query($queryBuilder->getSQL(), $parameters)
            ->fetchAll();

        $revisions = [];
        foreach ($variables as $variable) {
            $revisions[] = new Revision(
                $variable[self::REVISION_RESOURCE],
                $variable[self::REVISION_VERSION],
                $variable[self::REVISION_CREATED],
                $variable[self::REVISION_USER],
                $variable[self::REVISION_MESSAGE]
            );
        }
        return $revisions;
    }

    /**
     * @param Revision $revision
     * @return Triple[]
     */
    public function getData(Revision $revision)
    {
        $queryBuilder = $this->getQueryBuilder()
            ->select('*')
            ->from(self::DATA_TABLE_NAME)
            ->where(
                sprintf(' `%s` = ? AND `%s` = ? ', self::DATA_RESOURCE, self::DATA_VERSION)
            );

        $result = $this->getPersistence()->query(
            $queryBuilder->getSQL(),
            [$revision->getResourceId(), $revision->getVersion()]
        );

        $triples = [];
        while ($statement = $result->fetch()) {
            $triples[] = $this->prepareDataObject($statement, $this->getLocalModel()->getModelId());
        }

        return $triples;
    }

    /**
     *
     * @param Revision $revision
     * @param array $data
     * @return boolean
     */
    protected function saveData(Revision $revision, $data)
    {
        $dataToSave = [];

        foreach ($data as $triple) {
            $dataToSave[] = [
                self::DATA_RESOURCE => (string)$revision->getResourceId(),
                self::DATA_VERSION => (string)$revision->getVersion(),
                self::DATA_SUBJECT => (string)$triple->subject,
                self::DATA_PREDICATE => (string)$triple->predicate,
                self::DATA_OBJECT => (string)$triple->object,
                self::DATA_LANGUAGE => (string)$triple->lg
            ];
        }

        return $this->getPersistence()->insertMultiple(self::DATA_TABLE_NAME, $dataToSave);
    }

    /**
     * @inheritDoc
     */
    public function getRevisionsDataByQuery($query)
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->select('*');
        $queryBuilder->from(self::DATA_TABLE_NAME);

        $fieldName = self::DATA_OBJECT;
        $condition = "$fieldName {$this->getLike()} '%$query%'";
        $queryBuilder->where($condition)
            ->andWhere(sprintf("%s = '%s'", self::DATA_PREDICATE, OntologyRdfs::RDFS_LABEL));

        $result = $this->getPersistence()->query($queryBuilder->getSQL());
        $revisionsData = [];

        while ($statement = $result->fetch()) {
            $triple = $this->prepareDataObject($statement, $this->getLocalModel()->getModelId());
            $revisionsData[] = $triple;
        }
        return $revisionsData;
    }

    /**
     * @param array $statement
     * @param string $modelId
     * @return Triple
     */
    protected function prepareDataObject($statement, $modelId)
    {
        $triple = new Triple();
        $triple->modelid = $modelId;
        $triple->subject = $statement[self::DATA_SUBJECT];
        $triple->predicate = $statement[self::DATA_PREDICATE];
        $triple->object = $statement[self::DATA_OBJECT];
        $triple->lg = $statement[self::DATA_LANGUAGE];
        return $triple;
    }

    /**
     * @return QueryBuilder
     */
    protected function getQueryBuilder()
    {
        return $this->getPersistence()->getPlatForm()->getQueryBuilder();
    }


    /**
     * @return common_ext_Namespace
     */
    protected function getLocalModel()
    {
        return common_ext_NamespaceManager::singleton()->getLocalNamespace();
    }

    /**
     * @return string
     */
    protected function getLike()
    {
        return (new TaoSearchDriver())->like();
    }
}
