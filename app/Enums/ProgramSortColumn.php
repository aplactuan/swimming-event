<?php

namespace App\Enums;

enum ProgramSortColumn: string
{
    case Classification = 'classification';
    case AgeBracket = 'age_bracket';
    case Gender = 'gender';
    case Name = 'name';
}
