<?php

namespace Herrera\Phar\Update;

use Herrera\Json\Json;
use KevinGH\Version\Version;

/**
 * Manages the contents of an updates manifest file.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Manifest
{
    /**
     * The list of updates in the manifest.
     *
     * @var Update[]
     */
    private $updates;

    /**
     * Sets the list of updates from the manifest.
     *
     * @param Update[] $updates The updates.
     */
    public function __construct(array $updates = array())
    {
        $this->updates = $updates;
    }

    /**
     * Finds the most recent update and returns it.
     *
     * @param Version $version The current version.
     * @param boolean $major   Lock to major version?
     *
     * @return Update The update.
     */
    public function findRecent(Version $version, $major = false)
    {
        /** @var $current Update */
        $current = null;
        $major = $major ? $version->getMajor() : null;

        foreach ($this->updates as $update) {
            if ($major && ($major !== $update->getVersion()->getMajor())) {
                continue;
            }

            $test = $current ? $current->getVersion() : $version;

            if (false === $update->isNewer($test)) {
                continue;
            }

            $current = $update;
        }

        return $current;
    }

    /**
     * Returns the list of updates in the manifest.
     *
     * @return Update[] The updates.
     */
    public function getUpdates()
    {
        return $this->updates;
    }

    /**
     * Loads the manifest from a JSON encoded string.
     *
     * @param string $json The JSON encoded string.
     *
     * @return Manifest The manifest.
     */
    public static function load($json)
    {
        $j = new Json();

        return self::create($j->decode($json), $j);
    }

    /**
     * Loads the manifest from a JSON encoded file.
     *
     * @param string $file The JSON encoded file.
     *
     * @return Manifest The manifest.
     */
    public static function loadFile($file)
    {
        $json = new Json();

        return self::create($json->decodeFile($file), $json);
    }

    /**
     * Validates the data, processes it, and returns a new instance of Manifest.
     *
     * @param array $decoded The decoded JSON data.
     * @param Json  $json    The Json instance used to decode the data.
     *
     * @return Manifest The new instance.
     */
    private static function create($decoded, Json $json)
    {
        $json->validate(
            $json->decodeFile(PHAR_UPDATE_MANIFEST_SCHEMA),
            $decoded
        );

        $updates = array();

        foreach ($decoded as $update) {
            $updates[] = new Update(
                $update->name,
                $update->sha1,
                $update->url,
                Version::create($update->version)
            );
        }

        usort($updates, function (Update $a, Update $b) {
            return $a->getVersion()->compareTo($b->getVersion());
        });

        return new static($updates);
    }
}