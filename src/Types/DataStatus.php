<?php

namespace App\Types;

enum DataStatus: string
{
    case ACTIVE = 'active';
    case DELETED = 'deleted';
    case ARCHIVED = 'archived';
    case CANCELLED = 'cancelled';
    case DRAFT = 'draft';
}