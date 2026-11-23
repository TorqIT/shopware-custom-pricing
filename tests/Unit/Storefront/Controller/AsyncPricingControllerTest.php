<?php declare(strict_types=1);

namespace Torq\Shopware\CustomPricing\Tests\Unit\Storefront\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\Test\Generator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Torq\Shopware\CustomPricing\Storefront\Controller\AsyncPricingController;

/**
 * @covers \Torq\Shopware\CustomPricing\Storefront\Controller\AsyncPricingController
 */
class AsyncPricingControllerTest extends TestCase
{
    private SalesChannelRepository&MockObject $productRepository;

    private AsyncPricingController $controller;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(SalesChannelRepository::class);
        $this->controller = new AsyncPricingController($this->productRepository);
        $this->controller->setContainer(new Container());
    }

    /**
     * @dataProvider invalidProductIdProvider
     *
     * @param array<string, mixed> $query
     */
    public function testItRejectsAMissingOrMalformedProductIdWithoutSearching(array $query): void
    {
        $this->productRepository->expects(static::never())->method('search');

        $response = $this->controller->getPrice(new Request($query), Generator::generateSalesChannelContext());

        static::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        static::assertSame(['price' => ''], json_decode((string) $response->getContent(), true));
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidProductIdProvider(): iterable
    {
        yield 'missing' => [[]];
        yield 'empty' => [['productId' => '']];
        yield 'not an id' => [['productId' => 'abc']];
    }

    public function testAnArrayProductIdIsABadRequest(): void
    {
        $this->productRepository->expects(static::never())->method('search');

        $this->expectException(BadRequestException::class);

        $this->controller->getPrice(
            new Request(['productId' => ['019885b123f17489b83bda1fcd7c9bed']]),
            Generator::generateSalesChannelContext()
        );
    }

    public function testItAnswersNotFoundWithAnEmptyPriceForAProductThatIsNotVisible(): void
    {
        $this->productRepository->method('search')->willReturn(new EntitySearchResult(
            ProductDefinition::ENTITY_NAME,
            0,
            new ProductCollection(),
            null,
            new Criteria(),
            Context::createDefaultContext()
        ));

        $response = $this->controller->getPrice(
            new Request(['productId' => '019885b123f17489b83bda1fcd7c9bed']),
            Generator::generateSalesChannelContext()
        );

        static::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        static::assertSame(['price' => ''], json_decode((string) $response->getContent(), true));
    }
}
