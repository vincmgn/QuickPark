<?php

namespace App\Entity\Traits;

enum DataStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
    case DELETED = 'deleted';
    case ARCHIVED = 'archived';
    case DRAFT = 'draft';
    case MOCK = 'mock';
}
