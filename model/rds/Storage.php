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

class Storage
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
    
    public function __construct($persistenceId) {
        $this->persistence = \common_persistence_SqlPersistence::getPersistence($persistenceId);
    }
    
    public function addRevision($resourceId, $version, $created, $author, $message, $data) {
        $this->persistence->insert(
            self::REVISION_TABLE_NAME,
            array(
                self::REVISION_RESOURCE => $resourceId,
                self::REVISION_VERSION => $version,
                self::REVISION_USER => $author,
                self::REVISION_MESSAGE => $message,
                self::REVISION_CREATED => $created
            )
        );
        
        $revision = new Revision($this->persistence->lastInsertId(), $resourceId, $version, $created, $author, $message);

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
        
        $variables = $this->persistence->query($sql,$params);

        if ($variables->rowCount() != 1) {
            return null;
        }
        $variable = $variables->fetch();
        return new Revision($variable[self::REVISION_ID], $variable[self::REVISION_RESOURCE], $variable[self::REVISION_VERSION],
                $variable[self::REVISION_CREATED], $variable[self::REVISION_USER], $variable[self::REVISION_MESSAGE]);
        
    }
    
    public function getAllRevisions($resourceId) {
        $sql = 'SELECT * FROM ' . self::REVISION_TABLE_NAME.' WHERE ' . self::REVISION_RESOURCE . ' = ?';
        $params = array($resourceId);
        $variables = $this->persistence->query($sql, $params);
        
        $revisions = array();
        foreach ($variables as $variable) {
            $revisions[] = new Revision($variable[self::REVISION_ID], $variable[self::REVISION_RESOURCE], $variable[self::REVISION_VERSION],
                $variable[self::REVISION_CREATED], $variable[self::REVISION_USER], $variable[self::REVISION_MESSAGE]);
        }
        return $revisions;
    }
    
    public function getData(Revision $revision) {
        
        // retrieve data
        $query = 'SELECT * FROM '.self::DATA_TABLE_NAME.' WHERE '.self::DATA_REVISION.' = ?';
        $result = $this->persistence->query($query, array($revision->getId()));
        
        $triples = array();
        while ($statement = $result->fetch()) {
            $triple = new \core_kernel_classes_Triple();
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
    protected function saveData(Revision $revision, $data) {
        $columns = array(self::DATA_REVISION, self::DATA_SUBJECT, self::DATA_PREDICATE, self::DATA_OBJECT, self::DATA_LANGUAGE);
        
        $multipleInsertQueryHelper = $this->persistence->getPlatForm()->getMultipleInsertsSqlQueryHelper();
        $query = $multipleInsertQueryHelper->getFirstStaticPart(self::DATA_TABLE_NAME, $columns);
        foreach ($data as $triple) {
            $query .= $multipleInsertQueryHelper->getValuePart(self::DATA_TABLE_NAME, $columns, array(
                self::DATA_REVISION  => $this->persistence->quote($revision->getId()),
                self::DATA_SUBJECT   => $this->persistence->quote($triple->subject),
                self::DATA_PREDICATE => $this->persistence->quote($triple->predicate),
                self::DATA_OBJECT    => $this->persistence->quote($triple->object),
                self::DATA_LANGUAGE  => $this->persistence->quote($triple->lg)
            ));
        }
        
        $query = substr($query, 0, strlen($query) -1);
        $query .= $multipleInsertQueryHelper->getEndStaticPart();
        $success = $this->persistence->exec($query);
    }
}
