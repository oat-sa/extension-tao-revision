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
namespace oat\taoRevision\scripts\update;

use oat\tao\model\accessControl\func\AccessRule;
use oat\tao\model\accessControl\func\AclProxy;

/**
 * 
 * @author Joel Bout <joel@taotesting.com>
 */
class Updater extends \common_ext_ExtensionUpdater {
    
    /**
     * 
     * @param string $currentVersion
     * @return string $versionUpdatedTo
     */
    public function update($initialVersion) {
        
        $currentVersion = $initialVersion;
        
        //migrate from 1.0 to 1.0.1
        if ($currentVersion == '1.0') {
            AclProxy::applyRule(new AccessRule('grant', 'http://www.tao.lu/Ontologies/TAOItem.rdf#ItemAuthor',
                array('controller'=>'oat\\taoRevision\\controller\\History')));
            AclProxy::applyRule(new AccessRule('grant', 'http://www.tao.lu/Ontologies/TAOItem.rdf#TestAuthor',
                array('controller'=>'oat\\taoRevision\\controller\\History')));
            $currentVersion = '1.0.1';
        }
        
        return $currentVersion;
    }
}
