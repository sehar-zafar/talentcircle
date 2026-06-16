<?php

class GoogleMeetService {
    public static function createMeetLink(
        string $calendarId,
        string $summary,
        string $description,
        string $startDateTime,
        string $endDateTime,
        string $timezone,
        string $requestId
    ): array {
        // Lazy-load Google API client via vendor autoload if installed.
        $autoload = __DIR__ . '/../../../vendor/autoload.php';
        if (!file_exists($autoload)) {
            return ['error' => 'Google API client not installed. Run composer require google/apiclient:^2.0 in Talentcircle/backend.'];
        }
        require_once $autoload;

        // Service account credentials
        $saPath = __DIR__ . '/../../../storage/google-service-account.json';
        if (!file_exists($saPath)) {
            return ['error' => 'Missing service account file at backend/storage/google-service-account.json'];
        }

        $client = new Google_Client();
        $client->setAuthConfig($saPath);
        $client->setScopes(['https://www.googleapis.com/auth/calendar']);
        $client->setSubject(null);

        $service = new Google_Service_Calendar($client);

        $event = new Google_Service_Calendar_Event([
            'summary' => $summary,
            'description' => $description,
            'start' => [
                'dateTime' => $startDateTime,
                'timeZone' => $timezone,
            ],
            'end' => [
                'dateTime' => $endDateTime,
                'timeZone' => $timezone,
            ],
            // Request a Meet link
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => $requestId,
                    'conferenceSolutionKey' => [
                        'type' => 'hangoutsMeet',
                    ],
                ],
            ],
        ]);

        $created = $service->events->insert(
            $calendarId,
            $event,
            [
                'conferenceDataVersion' => 1,
            ]
        );

        $hangoutLink = $created->getHangoutLink();
        if (!$hangoutLink) {
            return ['error' => 'Google returned event but no hangoutLink.'];
        }

        return ['hangoutLink' => $hangoutLink, 'eventId' => $created->getId()];
    }
}

