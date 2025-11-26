<?php

namespace App\DTO;

use App\Models\Sport;

readonly class ValidatedTeamData
{
    public function __construct(
        public ?string $designation,
        public ?string $mascot,
        public ?array $sports,
    ) {}

    public static function fromArray(array $data): self
    {   
        // convert sports values to Sport enum instances
        $sports = [];
        if (isset($data['sports']) && is_array($data['sports'])) {
            $sports = array_map(function ($sport) {
                return $sport instanceof Sport ? $sport : Sport::tryFrom($sport);
            }, $data['sports']);
        }

        // remove nulls from invalid enums
        $sports = array_filter($sports);

        return new self(
            designation: isset($data['designation']) ? (string) $data['designation'] : null,
            mascot: isset($data['mascot']) ? (string) $data['mascot'] : null,
            sports: $sports,
        );
    }
}