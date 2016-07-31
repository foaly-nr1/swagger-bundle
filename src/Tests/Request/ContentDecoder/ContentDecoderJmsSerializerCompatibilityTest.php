<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\SwaggerBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\SwaggerBundle\Tests\Request\ContentDecoder;

use JMS\Serializer\Serializer;
use KleijnWeb\SwaggerBundle\Document\Specification\Operation;
use KleijnWeb\SwaggerBundle\Request\ContentDecoder;
use KleijnWeb\SwaggerBundle\Serialize\SerializationTypeResolver;
use KleijnWeb\SwaggerBundle\Serialize\Serializer\Factory\JmsSerializerFactory;
use KleijnWeb\SwaggerBundle\Serialize\Serializer\JmsSerializerAdapter;
use KleijnWeb\SwaggerBundle\Tests\Request\TestRequestFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class ContentDecoderJmsSerializerCompatibilityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContentDecoder
     */
    private $contentDecoder;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * Create serializer
     */
    protected function setUp()
    {
        $this->serializer = new JmsSerializerAdapter(JmsSerializerFactory::factory());

        $typeResolver = $this
            ->getMockBuilder(SerializationTypeResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $typeResolver
            ->expects($this->any())
            ->method('resolveOperationBodyType')
            ->willReturn(JmsAnnotatedResourceStub::class);

        $this->contentDecoder = new ContentDecoder($this->serializer, $typeResolver);
    }

    /**
     * @test
     */
    public function canDeserializeIntoObject()
    {
        $content = [
            'foo' => 'bar'
        ];
        $request = new Request([], [], [], [], [], [], json_encode($content));
        $request->headers->set('Content-Type', 'application/json');

        $operationDefinition = (object)[
            'parameters' => [
                (object)[
                    "in"     => "body",
                    "name"   => "body",
                    "schema" => (object)[
                        '$ref' => "#/definitions/JmsAnnotatedResourceStub"
                    ]
                ]
            ]
        ];

        $operationObject = Operation::createFromOperationDefinition((object)$operationDefinition);

        $actual = $this->contentDecoder->decodeContent($request, $operationObject);

        $expected = (new JmsAnnotatedResourceStub)->setFoo('bar');

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     *
     * @expectedException \KleijnWeb\SwaggerBundle\Exception\MalformedContentException
     */
    public function willThrowMalformedContentExceptionWhenDecodingFails()
    {
        $content = 'lkjhlkj';
        $request = TestRequestFactory::create($content);
        $request->headers->set('Content-Type', 'application/json');

        $operationObject = Operation::createFromOperationDefinition((object)[]);
        $this->contentDecoder->decodeContent($request, $operationObject);
    }

    /**
     * @test
     * @dataProvider contentTypeProvider
     *
     * @param string $contentType
     */
    public function willAlwaysDecodeJson($contentType)
    {
        $content = '{ "foo": "bar" }';
        $request = TestRequestFactory::create($content);
        $request->headers->set('Content-Type', $contentType);

        $operationDefinition = (object)[
            'parameters' => [
                (object)[
                    "in"     => "body",
                    "name"   => "body",
                    "schema" => (object)[
                        '$ref' => "#/definitions/JmsAnnotatedResourceStub"
                    ]
                ]
            ]
        ];

        $operationObject = Operation::createFromOperationDefinition((object)$operationDefinition);

        $actual   = $this->contentDecoder->decodeContent($request, $operationObject);
        $expected = (new JmsAnnotatedResourceStub)->setFoo('bar');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public static function contentTypeProvider()
    {
        return [
            ['application/json'],
            ['application/vnd.api+json']
        ];
    }
}
