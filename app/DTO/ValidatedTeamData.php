<?php

namespace App\DTO;

use App\Models\Sport;
use App\Models\TeamType;

readonly class ValidatedTeamData
{
    public function __construct(
        public string $organization,
        public string $designation,
        public string $conference,
        public ?string $mascot,
        public ?string $abbreviation,
        public ?string $color,
        public ?string $alternateColor,
        public ?array $logos,
        public ?array $socialMedia,
        public TeamType $type,
        public array $sports,
    ) {}

    public static function fromArray(array $data): self
    {
        // convert sports values to Sport enum instances
        $sports = [];
        if (isset($data['sports']) && is_array($data['sports'])) {
            $sports = array_map(function ($sport) {
                return $sport instanceof Sport ? $sport : Sport::from($sport);
            }, $data['sports']);
        }

        return new self(
            organization: (string) $data['organization'],
            designation: (string) $data['designation'],
            conference: (string) $data['conference'],
            mascot: $data['mascot'] ?? null,
            abbreviation: $data['abbreviation'] ?? null,
            color: $data['color'] ?? null,
            alternateColor: $data['alternate_color'] ?? null,
            logos: isset($data['logos']) && is_array($data['logos']) ? array_values($data['logos']) : null,
            socialMedia: isset($data['social_media']) && is_array($data['social_media']) ? array_values($data['social_media']) : null,
            type: TeamType::from($data['type']),
            sports: $sports,
        );
    }
}
