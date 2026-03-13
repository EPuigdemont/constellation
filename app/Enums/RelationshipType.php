<?php

declare(strict_types=1);

namespace App\Enums;

enum RelationshipType: string
{
    case ParentChild = 'parent_child';
    case Sibling = 'sibling';
}
