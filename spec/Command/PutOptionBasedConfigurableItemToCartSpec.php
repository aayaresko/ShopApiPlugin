<?php

declare(strict_types=1);

namespace spec\Sylius\ShopApiPlugin\Command;

use PhpSpec\ObjectBehavior;
use TypeError;

final class PutOptionBasedConfigurableItemToCartSpec extends ObjectBehavior
{
    function let(): void
    {
        $this->beConstructedWith('ORDERTOKEN', 'T_SHIRT_CODE', ['RED_OPTION_CODE'], 5);
    }

    function it_has_order_token(): void
    {
        $this->orderToken()->shouldReturn('ORDERTOKEN');
    }

    function it_has_product_code(): void
    {
        $this->product()->shouldReturn('T_SHIRT_CODE');
    }

    function it_has_options_code(): void
    {
        $this->options()->shouldReturn(['RED_OPTION_CODE']);
    }

    function it_has_quantity(): void
    {
        $this->quantity()->shouldReturn(5);
    }

    function it_throws_an_exception_if_order_token_is_not_a_string(): void
    {
        $this->beConstructedWith(new \stdClass(), 'T_SHIRT_CODE', ['RED_OPTION_CODE'], 1);

        $this->shouldThrow(TypeError::class)->duringInstantiation();
    }

    function it_throws_an_exception_if_product_code_is_not_a_string(): void
    {
        $this->beConstructedWith('ORDERTOKEN', new \stdClass(), ['RED_OPTION_CODE'], 1);

        $this->shouldThrow(TypeError::class)->duringInstantiation();
    }

    function it_throws_an_exception_if_options_are_empty(): void
    {
        $this->beConstructedWith('ORDERTOKEN', 'T_SHIRT_CODE', [], 1);

        $this->shouldThrow(\InvalidArgumentException::class)->duringInstantiation();
    }

    function it_throws_an_exception_if_quantity_is_not_less_then_0(): void
    {
        $this->beConstructedWith('ORDERTOKEN', 'T_SHIRT_CODE', ['RED_OPTION_CODE'], 0);

        $this->shouldThrow(\InvalidArgumentException::class)->duringInstantiation();
    }
}
