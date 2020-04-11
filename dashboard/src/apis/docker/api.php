<?php

/**
 * Copyright (c) 2020 Nadav Tasher
 * https://github.com/NadavTasher/Nockio/
 **/

include_once __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "base" . DIRECTORY_SEPARATOR . "api.php";

/**
 * Docker API for basic Docker functionality.
 */
class Docker
{

    // Default socket
    private const DOCKER_SOCKET = DIRECTORY_SEPARATOR . "var" . DIRECTORY_SEPARATOR . "run" . DIRECTORY_SEPARATOR . "docker.sock";

    /**
     * Builds an image from the path with the given tag.
     * @param string $path Path
     * @param string $tag Tag
     * @return array Results
     */
    public static function build($path, $tag)
    {
        // Create a new context
        $context = self::createContext("/build?t=$tag", true);
        // Set additional options
        // Create phar
        $file = self::temporary(".tar");
        $phar = new PharData($file);
        $phar->buildFromDirectory($path);
        // Add phar
        curl_setopt($context, CURLOPT_POSTFIELDS, file_get_contents($file));
//        curl_setopt($context, CURLOPT_HTTPHEADER, ["Content-Type: application/x-tar"]);
        // Execute and return
        $result = self::destroyContext($context);
        // Check result
        if ($result[0] < 300) {
            return [true, "Docker image built"];
        }
        return [false, "Docker build error"];
    }

    public static function run($tag, $ports = [], $volumes = [], $environment = [])
    {
        // Create a new context
        $context = self::createContext("/containers/create?name=$tag", true);
        // Set additional options
        $container = new stdClass();
        $container->Hostname = $tag;
        $container->Image = $tag;
        // Add phar
        curl_setopt($context, CURLOPT_POSTFIELDS, json_encode($container));
//        curl_setopt($context, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        // Execute and return
        $result = self::destroyContext($context);
        // Check result
        if ($result[0] < 300) {
            return [true, "Docker image built"];
        }
        return [false, "Docker build error"];
    }

    private static function stop($tag)
    {

    }

    private static function remove($tag)
    {

    }

    /**
     * Creates a new context.
     * @param string $endpoint Endpoint
     * @param bool $post Is POST
     * @return resource Context
     */
    private static function createContext($endpoint, $post = false)
    {
        // Create a context
        $context = curl_init("http://localhost$endpoint");
        // Set options
        curl_setopt($context, CURLOPT_UNIX_SOCKET_PATH, self::DOCKER_SOCKET);
        curl_setopt($context, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($context, CURLOPT_POST, $post);
        // Return context
        return $context;
    }

    private static function destroyContext($context)
    {
        // Execute the context
        $data = curl_exec($context);
        $code = curl_getinfo($context, CURLINFO_RESPONSE_CODE);
        // Close the context
        curl_close($context);
        // Return the result
        return [$code, $data];
    }

    /**
     * Creates a path for a temporary file.
     * @param string $postfix Path postfix
     * @return string Path
     */
    private static function temporary($postfix = "")
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . "temporary_" . Utility::random(10) . $postfix;
    }

}