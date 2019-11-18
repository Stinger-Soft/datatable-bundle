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

namespace StingerSoft\DatatableBundle\Orderer;

use StingerSoft\DatatableBundle\Column\ColumnInterface;
use StingerSoft\DatatableBundle\Exception\OrderedConfigurationException;
use StingerSoft\DatatableBundle\Table\TableInterface;

class TableOrderer implements TableOrdererInterface {

	/** @var array */
	private $weights;

	/** @var array */
	private $differed;

	/** @var integer */
	private $firstWeight;

	/** @var integer */
	private $currentWeight;

	/** @var integer */
	private $lastWeight;

	/**
	 * {@inheritdoc}
	 */
	public function order(TableInterface $table) {
		$this->reset();

		foreach($table->getColumns() as $column) {
			$options = $column->getColumnOptions();
			$position = $options['position'];
			
			if(empty($position)) {
				$this->processEmptyPosition($column);
			} elseif(is_string($position)) {
				$this->processStringPosition($column, $position);
			} else {
				$this->processArrayPosition($column, $position);
			}
		}

		asort($this->weights, SORT_NUMERIC);

		return array_keys($this->weights);
	}

	/**
	 * Processes an an empty position.
	 *
	 * @param ColumnInterface $column The column.
	 */
	private function processEmptyPosition(ColumnInterface $column) {
		$this->processWeight($column, $this->currentWeight);
	}

	/**
	 * Processes a string position.
	 *
	 * @param ColumnInterface $column   The column.
	 * @param string          $position The position.
	 */
	private function processStringPosition(ColumnInterface $column, $position) {
		if($position === 'first') {
			$this->processFirst($column);
		} else {
			$this->processLast($column);
		}
	}

	/**
	 * Processes an array position.
	 *
	 * @param ColumnInterface $column   The column.
	 * @param array           $position The position.
	 */
	private function processArrayPosition(ColumnInterface $column, array $position) {
		if(isset($position['before'])) {
			$this->processBefore($column, $position['before']);
		}

		if(isset($position['after'])) {
			$this->processAfter($column, $position['after']);
		}
	}

	/**
	 * Processes a first position.
	 *
	 * @param ColumnInterface $column The column.
	 */
	private function processFirst(ColumnInterface $column) {
		$this->processWeight($column, $this->firstWeight++);
	}

	/**
	 * Processes a last position.
	 *
	 * @param ColumnInterface $column The column.
	 */
	private function processLast(ColumnInterface $column) {
		$this->processWeight($column, $this->lastWeight + 1);
	}

	/**
	 * Processes a before position.
	 *
	 * @param ColumnInterface $column The column.
	 * @param string          $before The before column name.
	 */
	private function processBefore(ColumnInterface $column, $before) {
		if(!isset($this->weights[$before])) {
			$this->processDiffered($column, $before, 'before');
		} else {
			$this->processWeight($column, $this->weights[$before]);
		}
	}

	/**
	 * Processes an after position.
	 *
	 * @param ColumnInterface $column The column.
	 * @param string          $after  The after column name.
	 */
	private function processAfter(ColumnInterface $column, $after) {
		if(!isset($this->weights[$after])) {
			$this->processDiffered($column, $after, 'after');
		} else {
			$this->processWeight($column, $this->weights[$after] + 1);
		}
	}

	/**
	 * Processes a weight.
	 *
	 * @param ColumnInterface $column The column.
	 * @param integer         $weight The weight.
	 */
	private function processWeight(ColumnInterface $column, $weight) {
		foreach($this->weights as &$weightRef) {
			if($weightRef >= $weight) {
				$weightRef++;
			}
		}

		if($this->currentWeight >= $weight) {
			$this->currentWeight++;
		}

		$this->lastWeight++;

		$this->weights[$column->getPath()] = $weight;
		$this->finishWeight($column, $weight);
	}

	/**
	 * Finishes the weight processing.
	 *
	 * @param ColumnInterface $column   The column.
	 * @param integer         $weight   The weight.
	 * @param string          $position The position (null|before|after).
	 *
	 * @return integer The new weight.
	 */
	private function finishWeight(ColumnInterface $column, $weight, $position = null) {
		if($position === null) {
			foreach(array_keys($this->differed) as $position) {
				$weight = $this->finishWeight($column, $weight, $position);
			}
		} else {
			$name = $column->getPath();

			if(isset($this->differed[$position][$name])) {
				$postIncrement = $position === 'before';

				foreach($this->differed[$position][$name] as $differed) {
					$this->processWeight($differed, $postIncrement ? $weight++ : ++$weight);
				}

				unset($this->differed[$position][$name]);
			}
		}

		return $weight;
	}

	/**
	 * Processes differed.
	 *
	 * @param ColumnInterface $column   The column.
	 * @param string          $differed The differed form name.
	 * @param string          $position The position (before|after).
	 *
	 * @throws OrderedConfigurationException If the differed form does not exist.
	 */
	private function processDiffered(ColumnInterface $column, $differed, $position) {
//		if(!$column->getParent()->has($differed)) {
//			throw OrderedConfigurationException::createInvalidDiffered($column->getName(), $position, $differed);
//		}

		$this->differed[$position][$differed][] = $column;

		$name = $column->getPath();

		$this->detectCircularDiffered($name, $position);
		$this->detectedSymmetricDiffered($name, $differed, $position);
	}

	/**
	 * Detects circular before/after differed.
	 *
	 * @param string $name     The column name.
	 * @param string $position The position (before|after)
	 * @param array  $stack    The circular stack.
	 *
	 * @throws OrderedConfigurationException If there is a circular before/after differed.
	 */
	private function detectCircularDiffered($name, $position, array $stack = array()) {
		if(!isset($this->differed[$position][$name])) {
			return;
		}

		$stack[] = $name;

		foreach($this->differed[$position][$name] as $differed) {
			/** @var ColumnInterface $differed */
			$differedName = $differed->getPath();

			if($differedName === $stack[0]) {
				throw OrderedConfigurationException::createCircularDiffered($stack, $position);
			}

			$this->detectCircularDiffered($differedName, $position, $stack);
		}
	}

	/**
	 * Detects symmetric before/after differed.
	 *
	 * @param string $name     The form name.
	 * @param string $differed The differed form name.
	 * @param string $position The position (before|after).
	 *
	 * @throws \Ivory\OrderedForm\Exception\OrderedConfigurationException If there is a symetric before/after differed.
	 */
	private function detectedSymmetricDiffered($name, $differed, $position) {
		$reversePosition = ($position === 'before') ? 'after' : 'before';

		if(isset($this->differed[$reversePosition][$name])) {
			foreach($this->differed[$reversePosition][$name] as $diff) {
				/** @var ColumnInterface $diff */
				if($diff->getPath() === $differed) {
					throw OrderedConfigurationException::createSymetricDiffered($name, $differed);
				}
			}
		}
	}

	/**
	 * Resets the orderer.
	 */
	private function reset() {
		$this->weights = array();
		$this->differed = array(
			'before' => array(),
			'after' => array(),
		);

		$this->firstWeight = 0;
		$this->currentWeight = 0;
		$this->lastWeight = 0;
	}
}