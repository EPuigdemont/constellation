<?php

declare(strict_types=1);

namespace App\Enums;

enum RelationshipDirection: string
{
    case AToB = 'a_to_b';
    case BToA = 'b_to_a';
}
