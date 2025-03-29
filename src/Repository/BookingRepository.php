<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Status;
use App\Entity\Booking;
use App\Types\DataStatus;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function findActiveBookingsForUserParkings(User $user): array
    {
        return $this->createQueryBuilder('b')
            ->join('b.parking', 'p')
            ->where('p.owner = :user')
            ->andWhere('b.dataStatus = :dataStatus')
            ->setParameter('user', $user->getId())
            ->setParameter('dataStatus', DataStatus::ACTIVE)
            ->getQuery()
            ->getResult();
    }   
}
