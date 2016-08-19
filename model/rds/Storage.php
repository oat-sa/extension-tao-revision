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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA;
 * 
 */

namespace oat\taoRevision\model\rds;

use oat\taoRevision\model\RevisionNotFound;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\ServiceManager;
use oat\taoRevision\model\Revision;
use oat\taoRevision\model\RevisionStorage;
/**
 * Storage class for the revision data
 * 
 * @author Joel Bout <joel@taotesting.com>
 */
class Storage extends ConfigurableService implements  RevisionStorage
{
    const REVISION_TABLE_NAME = 'revision';
    
    const REVISION_ID = 'id';
    const REVISION_RESOURCE = 'resource';
    const REVISION_VERSION = 'version';
    const REVISION_USER = 'user';
    const REVISION_CREATED = 'created';
    const REVISION_MESSAGE = 'message';
    
    const DATA_TABLE_NAME = 'revision_data';
    
    const DATA_REVISION = 'revision';
    const DATA_SUBJECT = 'subject';
    const DATA_PREDICATE = 'predicate';
    const DATA_OBJECT = 'object';
    const DATA_LANGUAGE = 'language';
    
    /**
     * @var \common_persistence_SqlPersistence
     */
    private $persistence;
    
    public function getPersistence() {
        if (is_null($this->persistence)) {
            $this->persistence = ServiceManager::getServiceManager()->get(\common_persistence_Manager::SERVICE_KEY)->getPersistenceById($this->getOption('persistence'));
        }
        return $this->persistence;
    }
    
    public function addRevision($resourceId, $version, $created, $author, $message, $data) {
        $this->getPersistence()->insert(
            self::REVISION_TABLE_NAME,
            array(
                self::REVISION_RESOURCE => $resourceId,
                self::REVISION_VERSION => $version,
                self::REVISION_USER => $author,
                self::REVISION_MESSAGE => $message,
                self::REVISION_CREATED => $created
            )
        );
        
        $revision = new RdsRevision($this->getPersistence()->lastInsertId(self::REVISION_TABLE_NAME), $resourceId, $version, $created, $author, $message);

        $success = $this->saveData($revision, $data);
        return $revision;
    }
    
    /**
     * 
     * @param string $resourceId
     * @param string $version
     * @return NULL|\oat\taoRevision\model\rds\Revision
     */
    public function getRevision($resourceId, $version) {
        $sql = 'SELECT * FROM ' . self::REVISION_TABLE_NAME
        .' WHERE (' . self::REVISION_RESOURCE . ' = ? AND ' . self::REVISION_VERSION. ' = ?)';
        $params = array($resourceId, $version);
        
        $variables = $this->getPersistence()->query($sql,$params);

        if ($variables->rowCount() != 1) {
            throw new RevisionNotFound($resourceId, $version);
        }
        $variable = $variables->fetch();
        return new RdsRevision($variable[self::REVISION_ID], $variable[self::REVISION_RESOURCE], $variable[self::REVISION_VERSION],
                $variable[self::REVISION_CREATED], $variable[self::REVISION_USER], $variable[self::REVISION_MESSAGE]);
        
    }
    
    public function getAllRevisions($resourceId) {
        $sql = 'SELECT * FROM ' . self::REVISION_TABLE_NAME.' WHERE ' . self::REVISION_RESOURCE . ' = ?';
        $params = array($resourceId);
        $variables = $this->getPersistence()->query($sql, $params);
        
        $revisions = array();
        foreach ($variables as $variable) {
            $revisions[] = new RdsRevision($variable[self::REVISION_ID], $variable[self::REVISION_RESOURCE], $variable[self::REVISION_VERSION],
                $variable[self::REVISION_CREATED], $variable[self::REVISION_USER], $variable[self::REVISION_MESSAGE]);
        }
        return $revisions;
    }
    
    public function getData(Revision $revision) {
        if (!$revision instanceof RdsRevision) {
            throw new \common_exception_InconsistentData('Unexpected Revision class '.get_class($revision).' in '.__CLASS__);
        }
        $localModel = \common_ext_NamespaceManager::singleton()->getLocalNamespace();
        // retrieve data
        $query = 'SELECT * FROM '.self::DATA_TABLE_NAME.' WHERE '.self::DATA_REVISION.' = ?';
        $result = $this->getPersistence()->query($query, array($revision->getId()));
        
        $triples = array();
        while ($statement = $result->fetch()) {
            $triple = new \core_kernel_classes_Triple();
            $triple->modelid = $localModel->getModelId();
            $triple->subject = $statement[self::DATA_SUBJECT];
            $triple->predicate = $statement[self::DATA_PREDICATE];
            $triple->object = $statement[self::DATA_OBJECT];
            $triple->lg = $statement[self::DATA_LANGUAGE];
            $triples[] = $triple;
        }
        
        return $triples;
    }
    
    /**
     * 
     * @param Revision $revision
     * @param array $data
     * @return boolean
     */
    protected function saveData(RdsRevision $revision, $data) {
        if(empty($data)) {
            return false;
        }
        
        $columns = array(self::DATA_REVISION, self::DATA_SUBJECT, self::DATA_PREDICATE, self::DATA_OBJECT, self::DATA_LANGUAGE);
        
        $multipleInsertQueryHelper = $this->getPersistence()->getPlatForm()->getMultipleInsertsSqlQueryHelper();
        $query = $multipleInsertQueryHelper->getFirstStaticPart(self::DATA_TABLE_NAME, $columns);
        foreach ($data as $triple) {
            $query .= $multipleInsertQueryHelper->getValuePart(self::DATA_TABLE_NAME, $columns, array(
                self::DATA_REVISION  => $this->getPersistence()->quote($revision->getId()),
                self::DATA_SUBJECT   => $this->getPersistence()->quote($triple->subject),
                self::DATA_PREDICATE => $this->getPersistence()->quote($triple->predicate),
                self::DATA_OBJECT    => $this->getPersistence()->quote($triple->object),
                self::DATA_LANGUAGE  => $this->getPersistence()->quote($triple->lg)
            ));
        }
        
        $query = substr($query, 0, strlen($query) -1);
        $query .= $multipleInsertQueryHelper->getEndStaticPart();
        $success = $this->getPersistence()->exec($query);

        return $success;
    }
}
