<?php

namespace repositories;

use Ban;
use CasUser;
use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<Ban>
 */
class BanRepository extends EntityRepository
{

    public function getAllExpiredBans()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->not(Criteria::expr()->isNull('expires')))
            ->andWhere(Criteria::expr()->lte('expires', new DateTime()));
        $query = $this->createQueryBuilder("b")->addCriteria($criteria)->getQuery();
        return $query->getResult();
    }

    public function getAllCurrentBans()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->isNull('expires'))
            ->orWhere(Criteria::expr()->gt('expires', new DateTime()));
        $query = $this->createQueryBuilder("b")->addCriteria($criteria)->getQuery();
        return $query->getResult();
    }

    public function getCurrentBanOfUser(CasUser $user): ?Ban
    {
        $notExpiredCriteria = Criteria::create()
            ->where(Criteria::expr()->isNull('expires'))
            ->orWhere(Criteria::expr()->gt('expires', new DateTime()));

        $bannedUserCriteria = Criteria::create()
            ->where(Criteria::expr()->eq('banned', $user));

        $query = $this->createQueryBuilder("b")
            ->addCriteria($notExpiredCriteria)
            ->addCriteria($bannedUserCriteria)
            ->getQuery();
        return $query->getOneOrNullResult();
    }

}