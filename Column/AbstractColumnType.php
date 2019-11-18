<?php

/*
 * This file is part of the StingerSoft Datatable Bundle.
 *
 * (c) Oliver Kotte <oliver.kotte@stinger-soft.net>
 * (c) Florian Meyer <florian.meyer@stinger-soft.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace StingerSoft\DatatableBundle\Column;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Default column type which should be used for *ALL* specialized sub types.
 */
abstract class AbstractColumnType implements ColumnTypeInterface {

	/**
	 * @var string constant defining that a boolean option is true only for server side tables
	 */
	const SERVER_SIDE_ONLY = 'server';

	/**
	 * @var string constant defining that a boolean option is true only for client side tables
	 */
	const CLIENT_SIDE_ONLY = 'client';

	use ColumnTrait;

	/**
	 * {@inheritdoc}
	 * @see \StingerSoft\DatatableBundle\Column\ColumnTypeInterface::configureOptions()
	 */
	public function configureOptions(OptionsResolver $resolver, array $tableOptions = array()) {
	}

	/**
	 * {@inheritdoc}
	 * @see \StingerSoft\DatatableBundle\Column\ColumnTypeInterface::buildView()
	 */
	public function buildView(ColumnView $view, ColumnInterface $column, array $options) {
	}

	/**
	 * @inheritdoc
	 */
	public function buildData(ColumnInterface $column, array $options) {
	}

	/**
	 * {@inheritdoc}
	 * @see \StingerSoft\DatatableBundle\Column\ColumnTypeInterface::getParent()
	 */
	public function getParent() {
		return ColumnType::class;
	}

	public static function getBooleanValueDependingOnClientOrServer($optionValue, $serverSide) {
		if($optionValue === true) return true;
		if($optionValue === false) return false;
		return $serverSide ? $optionValue === self::SERVER_SIDE_ONLY : $optionValue === self::CLIENT_SIDE_ONLY;
	}
}