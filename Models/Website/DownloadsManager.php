<?php

namespace VoicesOfWynn\Models\Website;

use VoicesOfWynn\Models\Db;

class DownloadsManager
{
    public const DOWNLOAD_TYPE_MODFILE = 'jar';
    public const DOWNLOAD_TYPE_INSTALLER = 'exe';
    
    public const ROOT_DOWNLOADS_DIRECTORY = 'files/mod';
    public const INSTALLER_FILE_NAME = 'VoicesOfWynn-Installer-v1.1.jar'; //Both on the server and when downloaded
    private const FILE_NAME_FORMATS = 'VoicesOfWynn-MC{mcVersion}-v{version}.jar';

    public const FORGE_VERSIONS = ['1.12.2'];
    public const FABRIC_VERSIONS = ['1.21.1', '1.21', '1.20.2', '1.19.4', '1.19.3', '1.18.2'];


    /**
     * Lists all downloads, newest to oldest
     * @param array $versions Minecraft versions, out of which at least one needs to be supported by the downloads returned
     * @return ModDownload[] Array of ModDownloads objects
     * @throws \Exception
     */
    public function listDownloads(array $versions): array
    {
        $inString = rtrim(str_repeat('?,', count($versions)),',');
        $db = new Db('Website/DbInfo.ini');
        $result = $db->fetchQuery('
            SELECT * FROM download 
            WHERE mc_version IN ('.$inString.') 
            ORDER BY release_date DESC, version DESC, mc_version DESC
        ', $versions, true);

        if ($result === false) {
            return array();
        }

        $downloads = array();
        foreach ($result as $downloadData) {
            $downloads[] = new ModDownload($downloadData);
        }

        return $downloads;
    }

    /**
     * Sends the mod file to the client to download and increases the count of downloads for that file
     * It also sets all headers for the file download request
     * @param string $fileType Type of the download (installer/JAR file); must be one of the constants of this class
     * @param int $downloadId ID of the JAR file to download; the download count will be incremented for the download with this ID
     * @return bool false, if the download is not found, nothing otherwise (script is terminated with exit();
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function downloadFile(string $fileType, int $downloadId): bool
    {
        $db = new Db('Website/DbInfo.ini');
        
        if ($fileType === self::DOWNLOAD_TYPE_MODFILE) {
            $result = $db->fetchQuery('SELECT download_link,filename,mc_version,version,size FROM download WHERE download_id = ?', array($downloadId));
            if ($result === false) {
                return false;
            }

            $filePath = empty($result['download_link']) ? self::ROOT_DOWNLOADS_DIRECTORY.'/'.$result['filename'] : $result['download_link'];
            $fileName = str_replace('{mcVersion}', $result['mc_version'],
                str_replace('{version}', $result['version'], self::FILE_NAME_FORMATS)
            );
            $fileSize = $result['size'];
        } else if ($fileType === self::DOWNLOAD_TYPE_INSTALLER) {
            $filePath = self::ROOT_DOWNLOADS_DIRECTORY.'/'.self::INSTALLER_FILE_NAME;
            $fileName = self::INSTALLER_FILE_NAME;
            $fileSize = filesize($filePath);
        } else {
            throw new InvalidArgumentException('Invalid download type.');
        }

        if (!empty($result['download_link'])) {
            header('Location: '.$result['download_link']); //Remote download
        } else {
            $obContent = ob_get_contents(); //Pause the output bufferer to prevent memory overflow caused by "readfile()"
            ob_end_clean();

            header('Content-Description: File Transfer');
            header('Content-Type: application/java-archive');
            header('Content-Disposition: attachment; filename="'.$fileName.'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: '.$fileSize);
            readfile($filePath);

            ob_start(); //Resume the output bufferer
            echo $obContent;
        }
        return $db->executeQuery('UPDATE download SET downloaded_times = downloaded_times + 1 WHERE download_id = ?', array($downloadId));
    }

    /**
     * @param string $type Type of the release, must be one of the constants of the ModDownload class
     * @param string $mcVersion Version of Minecraft for which this download is made
     * @param string $wynnVersion Version of Wynncraft for which this download is made
     * @param string $version Version of the mod
     * @param string $changelog HTML text containing the changelog for the new version
     * @param string $downloadLink Direct download link (not required if $filename is provided and vice-versa)
     * @param string $filename Name of the file on the server. NOTE: The .jar file must be uploaded into the /files/mod directory when this function is run
     * @return int ID of the new release if the download has successfully been created
     * @throws UserException In case one or more of the provided strings is invalid
     */
    public function createDownload(string $type, string $mcVersion, string $wynnVersion, string $version, string $changelog, string $downloadLink, string $filename): int
    {
        $download = new ModDownload(array(
            'type' => $type,
            'mcVersion' => $mcVersion,
            'wynnVersion' => $wynnVersion,
            'version' => $version,
            'changelog' => $changelog,
            'download_link' => $downloadLink,
            'filename' => $filename,
            'date' => date('Y-m-d')
        ));

        $download->validate();

        $db = new Db('Website/DbInfo.ini');
        return $db->executeQuery('INSERT INTO download(release_type,mc_version,wynn_version,version,changelog,download.release_date,download_link,filename,size) VALUES (?,?,?,?,?,?,?,?,?)', array(
            $download->releaseType,
            $download->mcVersion,
            $download->wynnVersion,
            $download->version,
            $download->changelog,
            $download->releaseDate->format('Y-m-d'),
            $download->downloadLink,
            $download->fileName,
            $download->size
        ), true);
    }
}

