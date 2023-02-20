<?php

namespace mauricerenck\Podcaster;

use Kirby\Database\Database;

class PodcasterStatsMysql extends PodcasterStats
{
    private ?Database $database;

    public function __construct()
    {
        $podcasterDb = new PodcasterDatabase();
        $this->database = $podcasterDb->connect('mysql');
    }

    public function trackFeed($feed)
    {
        [$fields, $values] = $this->getFeedQueryData($feed);

        $query = 'INSERT INTO feeds(' . implode(',', $fields) . ') VALUES("' . implode(
                '","',
                $values
            ) . '") ON DUPLICATE KEY UPDATE downloads = downloads+1;';

        $this->database->execute($query);
    }

    public function upsertEpisode($feed, $episode, $trackingDate)
    {
        [$fields, $values] = $this->getEpisodeQueryData($feed, $episode, $trackingDate);

        $query = 'INSERT INTO episodes(' . implode(',', $fields) . ') VALUES("' . implode(
                '","',
                $values
            ) . '") ON DUPLICATE KEY UPDATE downloads=downloads+1;';

        $this->database->execute($query);
    }

    public function upsertUserAgents($feed, array $userAgentData, int $trackingDate)
    {
        [$podcastSlug, $downloadDate, $uuid] = $this->getUserAgentsQueryData($feed, $trackingDate);

        $uniqueHash = md5($userAgentData['os'] . $podcastSlug . $downloadDate);
        $fields = ['id', 'os', 'podcast_slug', 'uuid', 'created', 'downloads'];
        $values = [$uniqueHash, $userAgentData['os'], $podcastSlug, $uuid, $downloadDate, 1];

        $query = 'INSERT INTO os(' . implode(',', $fields) . ') VALUES("' . implode(
                '","',
                $values
            ) . '") ON DUPLICATE KEY UPDATE downloads=downloads+1;';

        $this->database->execute($query);

        $uniqueHash = md5($userAgentData['app'] . $podcastSlug . $downloadDate);
        $fields = ['id', 'useragent', 'podcast_slug', 'uuid', 'created', 'downloads'];
        $values = [$uniqueHash, $userAgentData['app'], $podcastSlug, $uuid, $downloadDate, 1];

        $query = 'INSERT INTO useragents(' . implode(',', $fields) . ') VALUES("' . implode(
                '","',
                $values
            ) . '") ON DUPLICATE KEY UPDATE downloads=downloads+1;';
        $this->database->execute($query);

        $uniqueHash = md5($userAgentData['device'] . $podcastSlug . $downloadDate);
        $fields = ['id', 'device', 'podcast_slug', 'uuid', 'created', 'downloads'];
        $values = [$uniqueHash, $userAgentData['device'], $podcastSlug, $uuid, $downloadDate, 1];

        $query = 'INSERT INTO devices(' . implode(',', $fields) . ') VALUES("' . implode(
                '","',
                $values
            ) . '") ON DUPLICATE KEY UPDATE downloads=downloads+1;';
        $this->database->execute($query);
    }

    public function getDownloadsGraphData($podcast, $year, $month): object|bool
    {
        $query = 'SELECT DAY(created) AS day, SUM(downloads) AS downloads FROM episodes WHERE podcast_slug = "' . $podcast . '" AND YEAR(created) = ' . $year . ' AND MONTH(created) = ' . $month . '  GROUP BY created';

        return $this->database->query($query);
    }

    public function getQuickReports($podcast, $year, $month): object|bool
    {
        $query = 'SELECT DAY(created) AS day, SUM(downloads) AS downloads FROM episodes WHERE podcast_slug = "' . $podcast . '" AND YEAR(created) = ' . $year . ' AND MONTH(created) = ' . $month . '  GROUP BY created';

        return $this->database->query($query);
    }

    public function getEpisodeGraphData($podcast, $episode): object|bool
    {
        $query = 'SELECT created AS date, downloads FROM episodes WHERE podcast_slug = "' . $podcast . '" AND episode_slug = "' . $episode . '";';

        return $this->database->query($query);
    }

    public function getTopEpisodesByMonth($podcast, $year, $month): object|bool
    {
        $query = 'SELECT episode_name AS title,episode_slug AS slug, SUM(downloads) AS downloads FROM episodes WHERE podcast_slug = "' . $podcast . '" AND YEAR(created) = ' . $year . ' AND MONTH(created) = ' . $month . '  GROUP BY episode_slug LIMIT 10';

        return $this->database->query($query);
    }

    public function getTopEpisodes($podcast): object|bool
    {
        $query = 'SELECT episode_name AS title,episode_slug AS slug, SUM(downloads) AS downloads FROM episodes WHERE podcast_slug = "' . $podcast . '" GROUP BY episode_slug LIMIT 10';

        return $this->database->query($query);
    }
}