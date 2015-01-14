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
 * 
 */

namespace oat\taoRevision\controller;

use oat\taoRevision\model\mock\Revision;
use oat\taoRevision\model\RepositoryProxy;
/**
 * Sample controller
 *
 * @author Open Assessment Technologies SA
 * @package taoRevision
 * @license GPL-2.0
 *
 */
class History extends \tao_actions_CommonModule {

    /**
     * initialize the services
     */
    public function __construct(){
        parent::__construct();
    }

    /**
     * 
     */
    public function index() {
        $resource = new \core_kernel_classes_Resource($this->getRequestParameter('id'));
        $revisions = RepositoryProxy::getRevisions($resource->getUri());

        $returnRevision = array();
        foreach($revisions as $revision){

            $user = new \core_kernel_classes_Resource($revision->getAuthorId());
            if($label = $user->getLabel() === ""){
                $label = '('.$revision->getAuthorId().')';
            }

            $returnRevision[] = array(
                'id'        => $revision->getIdentifier(),
                'modified'  => \tao_helpers_Date::displayeDate($revision->getDateCreated()),
                'author'    => $label,
                'message'   => $revision->getMessage(),
            );
        }
        
        $this->setData('resourceLabel', $resource->getLabel());
        $this->setData('id', $resource->getUri());
        $this->setData('revisions', $returnRevision);
        $this->setView('History/index.tpl');
    }

    public function restoreRevision(){
        $revision = new Revision($this->getRequestParameter('revisionId'));

        $newRevision = $revision->restore('restored');
        //get the user to display it
        $user = new \core_kernel_classes_Resource($newRevision->getAuthorId());
        if($label = $user->getLabel() === ""){
            $label = '('.$revision->getAuthorId().')';
        }
        $this->returnJson(array(
                'success'   => true,
                'id'        => $newRevision->getIdentifier(),
                'modified'  => \tao_helpers_Date::displayeDate($newRevision->getDateCreated()),
                'author'    => $label,
                'message'   => $newRevision->getMessage()
            ));
    }

    public function commitResource(){

        $resource = new \core_kernel_classes_Resource($this->getRequestParameter('id'));
        $message = $this->getRequestParameter('message');

        //commit a new revision of the resource
        $revision = RepositoryProxy::commit($resource->getUri(), $message);

        //get the user to display it
        $user = new \core_kernel_classes_Resource($revision->getAuthorId());
        if($label = $user->getLabel() === ""){
            $label = '('.$revision->getAuthorId().')';
        }


        $this->returnJson(array(
                'success'   => true,
                'id'        => $revision->getIdentifier(),
                'modified'  => \tao_helpers_Date::displayeDate($revision->getDateCreated()),
                'author'    => $label,
                'message'   => $revision->getMessage()
            ));
    }
}