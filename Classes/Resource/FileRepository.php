<?php

namespace Colorcube\FileRefList\Resource;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Resource\AbstractRepository;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Repository for accessing files
 * it also serves as the public API for the indexing part of files in general
 */
class FileRepository extends AbstractRepository
{
    /**
     * The main object type of this class. In some cases (fileReference) this
     * repository can also return FileReference objects, implementing the
     * common FileInterface.
     *
     * @var string
     */
    protected $objectType = File::class;

    /**
     * Main File object storage table. Note that this repository also works on
     * the sys_file_reference table when returning FileReference objects.
     *
     * @var string
     */
    protected $table = 'sys_file';

    /**
     * Creates an object managed by this repository.
     *
     * @param array $databaseRow
     * @return File
     */
    protected function createDomainObject(array $databaseRow)
    {
        return $this->factory->getFileObject($databaseRow['uid'], $databaseRow);
    }

    /**
     * Find FileReference objects by relation to other records
     *
     * @param int $pid
     * @return array An array of objects, empty if no objects found
     * @throws \InvalidArgumentException
     * @api
     */
    public function findByPid($pid)
    {
        $itemList = [];
        if (!MathUtility::canBeInterpretedAsInteger($pid)) {
            throw new \InvalidArgumentException(
                'UID of related record has to be an integer. UID given: "' . $uid . '"',
                1316789798
            );
        }
        $referenceUids = [];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_reference');

        //$queryBuilder->setRestrictions(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
        //$queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $res = $queryBuilder
            ->select('*')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)
                )
            )
            ->orderBy('sorting_foreign')
            ->execute();

        while ($row = $res->fetch()) {
            $referenceUids[] = $row['uid'];
        }

        #DebuggerUtility::var_dump($referenceUids);

        if (!empty($referenceUids)) {
            $referenceUids = array_unique($referenceUids);
            foreach ($referenceUids as $referenceUid) {
                try {
                    // Just passing the reference uid, the factory is doing workspace
                    // overlays automatically depending on the current environment
                    $file = $this->factory->getFileReferenceObject($referenceUid);
                    $itemList[$file->getIdentifier()] = $file;
                } catch (ResourceDoesNotExistException $exception) {
                    // No handling, just omit the invalid reference uid
                }
            }
        }

        # DebuggerUtility::var_dump($itemList);

        return $itemList;
    }

    /**
     * Find FileReference objects by uid
     *
     * @param int $uid The UID of the sys_file_reference record
     * @return FileReference|bool
     * @throws \InvalidArgumentException
     * @api
     */
    public function findFileReferenceByUid($uid)
    {
        if (!MathUtility::canBeInterpretedAsInteger($uid)) {
            throw new \InvalidArgumentException('The UID of record has to be an integer. UID given: "' . $uid . '"', 1316889798);
        }
        try {
            $fileReferenceObject = $this->factory->getFileReferenceObject($uid);
        } catch (\InvalidArgumentException $exception) {
            $fileReferenceObject = false;
        }
        return $fileReferenceObject;
    }


    /**
     * Return a file index repository
     *
     * @return FileIndexRepository
     */
    protected function getFileIndexRepository()
    {
        return FileIndexRepository::getInstance();
    }
}
