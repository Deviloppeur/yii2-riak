<?php

/**
 * File Client.php
 *
 * PHP version 5.4+
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @author    Philippe Gaultier <pgaultier@ibitux.com>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  riak
 * @package   sweelix.yii2.riak
 */

namespace sweelix\yii2\riak;

use sweelix\curl\Request;
use sweelix\curl\Response;
use yii\base\Component;
use Yii;

/**
 * Class Client
 *
 * The class is handle request & response to DB (noSql) server
 *
 * @author    Christophe Latour <clatour@ibitux.com>
 * @author    Philippe Gaultier <pgaultier@ibitux.com>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   XXX
 * @link      http://www.sweelix.net
 * @category  nosql
 * @package   sweelix.nosql
 * @since     XXX
 */
class Client extends Component
{

    /**
     * @var string base url
     */
    private $dsn;

    /**
     * @var array Base path
     */
    public static $apiMap = [
        'buckets' => '/buckets',
        'listBuckets' => '/buckets?buckets=true',
        'bucketProperties' => '/buckets/{bucket}/props',
        'bucketCounters' => '/buckets/{bucket}/counters/{key}',
        'listBucketKeys' => '/buckets/{bucket}/keys?keys=true',
        'objectKey' => '/buckets/{bucket}/keys/{key}',
        'linkWalking' => '/buckets/{bucket}/keys/{key}/',
        'secondaryIndexes' => '/buckets/{bucket}/index/{index_name}/{index_value}/{index_end}',
        'mapReduce' => '/mapred'
    ];

    /**
     * Get base url
     *
     * @return string
     * @since  XXX
     */
    public function getDsn()
    {
        return $this->dsn;
    }

    /**
     * Define the base url
     *
     * @param string $dsn base url
     *
     * @return void
     * @since  XXX
     */
    public function setDsn($dsn)
    {
        $this->dsn = rtrim($dsn, '/');
    }


    #######################################################################
    ################             OBJECT METHODS             ###############
    #######################################################################

    /**
     * Create an object into the selected bucket
     *
     * @param string $bucketName           name of the bucket
     * @param string $objectKey            key of the object to create (will be created if key is null)
     * @param mixed  $content              content which will be json enconded
     * @param array  $additionalParameters additional parameters to append to the query string
     * @param array  $additionalHeaders    additional headers (meta, link, ...)
     *
     * @return Response
     * @since  XXX
     * @see    http://docs.basho.com/riak/latest/dev/references/http/store-object/
     */
    public function storeObject(
        $bucketName,
        $objectKey = null,
        $content = null,
        $additionalParameters = array(),
        $additionalHeaders = array()
    ) {
        $url = $this->prepareUrl('objectKey', array(
            '{bucket}' => $bucketName,
            '{key}' => $objectKey
        ));
        $request = new Request($url);

        if ($objectKey === null) {
            $request->setMethod('POST');
            Yii::info('StoreObject request : @POST ' . $url . "\n", __METHOD__);
        } else {
            Yii::info('StoreObject request : @PUT ' . $url . "\n", __METHOD__);
            $request->setMethod('PUT');
        }

        Yii::info('StoreObject body : ' . var_export($content, true) . "\n", __METHOD__);
        Yii::info('StoreObject headers : ' . var_export($additionalHeaders, true) . "\n", __METHOD__);

        $request->setHeaders($additionalHeaders);
        $request->setUrlParameters($additionalParameters);
        $request->setBody($content);

        $response = $request->execute();
        Yii::info('StoreObject response : ' . var_export($response, true) . "\n", __METHOD__);
        return $response;
    }

    /**
     * Fetch an object from the selected bucket
     *
     * @param string $bucketName          Name of the bucket.
     * @param string $objectKey           Key of the object to fetch.
     * @param array $additionalParameters The additional get parameters.
     * @param array $additionalHeaders    The additionnal request headers.
     *
     * @return Response The request response
     * @since  XXX
     * @see    http://docs.basho.com/riak/latest/dev/references/http/fetch-object/
     */
    public function getObject($bucketName, $objectKey, $additionalParameters = array(), $additionalHeaders = array())
    {
        $url = $this->prepareUrl('objectKey', array(
            '{bucket}' => $bucketName,
            '{key}' => $objectKey
        ));
        $request = new Request($url);
        Yii::info('GetObject request : @GET ' . $url . "\n", __METHOD__);

        $request->setHeaders($additionalHeaders);
        $request->setUrlParameters($additionalParameters);

        $response = $request->execute();
        Yii::info('GetObject response : ' . var_export($response, true) . "\n", __METHOD__);
        return $response;
    }

    /**
     * Delete an object from the selected bucket
     *
     * @param string $bucketName          name of the bucket
     * @param string|array $objectKey     key or array of keys of object(s) to delete
     * @param array $additionalParameters additional parameters to append to the query string
     *
     * @return Response
     * @since  XXX
     * @see    http://docs.basho.com/riak/latest/dev/references/http/delete-object/
     */
    public function deleteObject($bucketName, $objectKey, $additionalParameters = array())
    {
        $url = $this->prepareUrl('objectKey', array(
            '{bucket}' => $bucketName,
            '{key}' => $objectKey
        ));
        $request = new Request($url);
        Yii::info('DeleteObject request : @DELETE ' . $url . "\n", __METHOD__);

        $request->setMethod('DELETE');
        $request->setUrlParameters($additionalParameters);

        $response = $request->execute();
        Yii::info('DeleteObject response ' . var_export($response, true) . "\n", __METHOD__);
        return $response;
    }

    #######################################################################
    ################            COUNTER METHODS             ###############
    #######################################################################

    /**
     * Update counters for the counterKey ($counterKey) in the bucket ($bucketName).
     *
     * @param string $bucketName           The bucket name.
     * @param string $counterKey           The counter key to update.
     * @param int    $incrementalValue     The value to add to counter.
     *
     * @return Response The request response.
     * @since  XXX
     * @see    http://docs.basho.com/riak/latest/dev/references/http/counters/
     */
    public function updateCounter($bucketName, $counterKey, $incrementalValue)
    {
        $url = $this->prepareUrl('bucketCounters', array(
            '{bucket}' => $bucketName,
            '{key}' => $counterKey
        ));
        $request = new Request($url);
        Yii::info('UpdateCounters request : @POST ' . $url . "\n", __METHOD__);
        Yii::info('UpdateCounters body : ' . var_export($incrementalValue, true) . "\n", __METHOD__);

        $request->setMethod('POST');
        $headers['Content-Type'] = 'application/json';
        $request->setBody("$incrementalValue");

        $response = $request->execute();
        Yii::info('UpdateCounters response : @POST ' . $url . "\n", __METHOD__);
        return $response;
    }

    /**
     * Get the counter ($counterKey) in the bucket ($bucket)
     *
     * @param string $bucketName           The bucket name.
     * @param string $counterKey           The counter to get.
     *
     * @return Response The request response.
     * @since  XXX
     * @see    http://docs.basho.com/riak/latest/dev/references/http/counters/
     */
    public function getCounter($bucketName, $counterKey)
    {
        $url = $this->prepareUrl('bucketCounters', array(
            '{bucket}' => $bucketName,
            '{key}' => $counterKey
        ));
        $request = new Request($url);
        Yii::info('GetCounters request : @GET ' . $url . "\n", __METHOD__);

        $request->setMethod('GET');

        $response = $request->execute();
        Yii::info('GetCounters response : ' . var_export($response, true) . "\n", __METHOD__);
        return $response;
    }

    #######################################################################
    ################            BUCKET METHODS              ###############
    #######################################################################

    /**
     * List bucket
     *
     * @return Response
     * @since  XXX
     * @see    http://docs.basho.com/riak/latest/dev/references/http/list-buckets/
     */
    public function listBuckets()
    {
        $url = $this->prepareUrl(['listBuckets']);
        $request = new Request($url);
        Yii::info('List buckets : @GET ' . $url . "\n", __METHOD__);

        $response = $request->execute();
        Yii::info('List buckets response : ' . var_export($response, true) . "\n", __METHOD__);

        return $response;
    }

    /**
     * List bucket keys
     *
     * @param string  $bucketName The bucket name to list its keys
     * @param boolean $stream     Whether to stream keys
     *
     * @return Response
     * @since  XXX
     * @see    http://docs.basho.com/riak/latest/dev/references/http/list-keys/
     */
    public function listBucketKeys($bucketName, $stream = false)
    {
        $url =  $this->prepareUrl(['listBucketKeys'], [
            '{bucket}' => $bucketName,
            '{type}' => $stream === false ? 'true' : 'stream'
        ]);

        $request = new Request($url);
        Yii::info('List bucket keys : @GET ' . $url . "\n", __METHOD__);

        $response = $request->execute();
        Yii::info('List bucket keys response : ' . var_export($response, true), __METHOD__);

        return $response;
    }


    /**
     * Reset bucket properties
     *
     * @param string $bucketName The bucket name
     *
     * @return Response
     * @since  XXX
     * @see    http://docs.basho.com/riak/latest/dev/references/http/reset-bucket-props/
     */
    public function resetBucketProperties($bucketName)
    {
        $url = $this->prepareUrl(['bucketProperties'], [
            '{bucket}' => $bucketName
        ]);

        $request = new Request($url);
        $request->setMethod('DELETE');
        Yii::info('Reset bucket properties : @DELETE ' . $url . "\n", __METHOD__);

        $response = $request->execute();
        Yii::info('Reset bucket properties response : ' . var_export($response, true), __METHOD__);
        return $response;
    }

    /**
     * Update the bucket properties
     *
     * @param string $bucketName The bucket to update
     * @param array  $properties The bucket properties
     *
     * @return Response The request response.
     * @since  XXX
     * @see    http://docs.basho.com/riak/latest/dev/references/http/set-bucket-props/
     */
    public function setBucketProperties($bucketName, array $properties)
    {
        $url = $this->prepareUrl('bucketProperties', array(
            '{bucket}' => $bucketName
        ));
        $request = new Request($url);
        Yii::info('Set bucket properties : @PUT' . $url . "\n", __METHOD__);
        Yii::info('Set bucket properties body : ' . var_export($properties, true) . "\n", __METHOD__);

        $request->setMethod('PUT');
        $request->setHeaders(array(
            'Content-Type' => 'application/json'
        ));
        $request->setBody(json_encode($properties));

        $response = $request->execute();
        Yii::info('Set bucket properties response : ' . var_export($response, true) . "\n", __METHOD__);

        return $response;
    }

    /**
     * Return the bucket properties.
     *
     * @param string $bucketName the bucket name to get properties
     *
     * @return Response The request response
     * @since  XXX
     * @see    http://docs.basho.com/riak/latest/dev/references/http/get-bucket-props/
     */
    public function getBucketProperties($bucketName)
    {
        $url = $this->prepareUrl('bucketProperties', array(
            '{bucket}' => $bucketName
        ));

        Yii::info('AlterBucket request : @GET' . $url . "\n", __METHOD__);

        $request = new Request($url);

        $response = $request->execute();
        Yii::info('AlterBucket response : ' . var_export($response, true) . "\n", __METHOD__);

        return $response;
    }

    #######################################################################
    ################            QUERY METHODS               ###############
    #######################################################################

    /**
     * Query an object with SecondaryIndexes
     *
     * @param string $bucketName           Name of the bucket.
     * @param array  $indexName            Indexed of object to query.
     * @param string $indexValue           The value searched (Or start value if endValue is set)
     * @param string $indexEndValue        The end value searched
     * @param array  $additionalParameters An array which represents GET parameters like : array('key' => 'value');
     *
     * @return Response object with the result of query
     * @since  XXX
     * @see    http://docs.basho.com/riak/latest/dev/references/http/secondary-indexes/
     */
    public function queryIndexes(
        $bucketName,
        $indexName,
        $indexValue,
        $indexEndValue = null,
        $additionalParameters = array()
    ) {
        $url = $this->prepareUrl('secondaryIndexes', array(
            '{bucket}' => $bucketName,
            '{index_name}' => $indexName,
            '{index_value}' => $indexValue,
            '{index_end}' => $indexEndValue
        ));
        $request = new Request($url);
        Yii::info('QueryIndexes request : @GET ' . $url . "\n", __METHOD__);
        $request->setMethod('GET');
        $request->setUrlParameters($additionalParameters);
        $response = $request->execute();
        Yii::info('QueryIndexes response : ' . var_export($response, true) . "\n", __METHOD__);
        return $response;
    }

    /**
     * Query an object with map/reduce/link
     *
     * @param string $mapReduce is a json string of link's parameter to query
     *
     * Example of param is: json_encode([
     *     'inputs' => [
     *         ['input', 'p1'], ['input', 'p2']
     *      ],
     *      'query' => [
     *          'map' => [
     *              'language' => 'javascript',
     *              'source' => 'javascript_function',
     *              'link' => array('bucket' => 'myjs',
     *              'key' => 'mymap',
     *              'keep' => false
     *           ,
     *            'reduce' => array('language' => 'javascript', 'source' => 'javascript_function'))
     *            ]);
     *
     * @return Response object with the result of query
     * @since XXX
     * @see http://docs.basho.com/riak/latest/dev/references/http/mapreduce/
     */
    public function queryMapReduce($mapReduce)
    {
        $url = $this->prepareUrl('mapReduce');
        $request = new Request($url);

        Yii::info('QueryMapReduce request : @POST ' . $url . "\n", __METHOD__);
        Yii::info('QueryMapReduce body : ' . var_export($mapReduce, true) . "\n", __METHOD__);
        $request->setMethod('POST');
        $additionalHeaders['Content-Type'] = 'application/json';
        $request->setHeaders($additionalHeaders);
        $request->setBody($mapReduce);
        $response = $request->execute();
        Yii::info('QueryMapReduce response : ' . var_export($response, true) . "\n", __METHOD__);
        return $response;
    }

    /**
     * Query an object with link-walking
     *
     * @param string $bucketName name of the bucket
     * @param string $objectKey  key of the object to query
     * @param string $links      is a string of link's parameter to query
     *
     * Example of param for $links is: 'test,_,1/_,next,1';
     *
     * @return Response object with the result of query
     * @since  XXX
     * @see    http://docs.basho.com/riak/latest/dev/references/http/link-walking/
     */
    public function queryLinks($bucketName, $objectKey, $links)
    {
        $url = $this->prepareUrl('linkWalking', array(
            '{bucket}' => $bucketName,
            '{key}' => $objectKey,
            'linkParams' => $links
        ));
        $request = new Request($url);
        Yii::info('QueryLink request : @GET ' . $url . "\n", __METHOD__);
        $request->setMethod('GET');
        $additionalHeaders['Content-Type'] = 'multipart/mixed';
        $request->setHeaders($additionalHeaders);

        $response = $request->execute();
        $response = $response->extractMultipartDataAsResponse();

        Yii::info('QueryLink response : ' . var_export($response, true) . "\n", __METHOD__);
        return $response;
    }

    #######################################################################
    ################            PRIVATE METHODS             ###############
    #######################################################################

    /**
     * Create the full url
     *
     * @param string $map       the path key
     * @param array $parameters the parameters to apply to the path
     *
     * @return string full url
     * @since XXX
     */
    private function prepareUrl($map, $parameters = null)
    {
        $urlPath = static::$apiMap[$map];
        if ($map === 'linkWalking') {
            $urlPath = str_replace('{bucket}', $parameters['{bucket}'], $urlPath);
            $urlPath = str_replace('{key}', $parameters['{key}'], $urlPath);
            foreach ($parameters['linkParams'] as $linkParams) {
                $urlPath .= $linkParams[0] . '/';
            }
        } else {
            if (is_array($parameters) === true) {
                $urlPath = str_replace(array_keys($parameters), array_values($parameters), $urlPath);
            }
        }
        $urlPath = rtrim($urlPath, '/');
        return $this->getDsn() . $urlPath;
    }
}
