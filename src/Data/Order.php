<?php

declare(strict_types=1);

namespace WebChemistry\Invoice\Data;

use Nette\SmartObject;
use WebChemistry\Invoice\InvoiceException;

class Order {

	use SmartObject;

	/** @var string|int */
	private $number;

	/** @var \DateTime */
	private $dueDate;

	/** @var Account */
	private $account;

	/** @var PaymentInformation */
	private $payment;

	/** @var \DateTime */
	private $created;

	/** @var Item[] */
	private $items = [];

	/** @var bool */
	private $hasPriceWithTax = FALSE;

	/**
	 * @param string|int $number
	 * @param \DateTime $dueDate
	 * @param Account $account
	 * @param PaymentInformation $payment
	 * @param \DateTime|NULL $created
	 * @param bool $hasPriceWithTax
	 */
	public function __construct($number, ?\DateTime $dueDate, ?Account $account, PaymentInformation $payment,
								\DateTime $created = NULL, bool $hasPriceWithTax = FALSE) {
		$this->number = $number;
		$this->dueDate = $dueDate;
		$this->account = $account;
		$this->payment = $payment;
		$this->created = $created ? : new \DateTime();
		$this->hasPriceWithTax = $hasPriceWithTax;

		$this->validate();
	}

	/**
	 * @return bool
	 */
	public function hasPriceWithTax(): bool {
		return $this->hasPriceWithTax;
	}

	/**
	 * @param string $name
	 * @param int|float $price
	 * @param int|float $count
	 * @return Item
	 */
	public function addItem(string $name, $price, $count = 1) {
		return $this->items[] = new Item($name, $price, $count);
	}

	/**
	 * Validates properties
	 *
	 * @throws InvoiceException
	 */
	private function validate() {
		if (!$this->number || !is_string($this->number) || !is_numeric($this->number)) {
			throw InvoiceException::wrongType('non-empty string or numeric', $this->number);
		}
	}

	/////////////////////////////////////////////////////////////////

	/**
	 * @return int|string
	 */
	public function getNumber() {
		return $this->number;
	}

	/**
	 * @return \DateTime
	 */
	public function getDueDate(): ?\DateTime {
		return $this->dueDate;
	}

	/**
	 * @return Account
	 */
	public function getAccount(): ?Account {
		return $this->account;
	}

	/**
	 * @return PaymentInformation
	 */
	public function getPayment(): PaymentInformation {
		return $this->payment;
	}

	/**
	 * @return \DateTime
	 */
	public function getCreated(): \DateTime {
		return $this->created;
	}

	/**
	 * @return Item[]
	 */
	public function getItems(): array {
		return $this->items;
	}

	/**
	 * @param bool $useTax
	 * @return float
	 */
	public function getTotalPrice(bool $useTax = false): float {
		$total = 0;
		if ($useTax && $this->getPayment()->getTax() !== NULL && !$this->hasPriceWithTax()) {
			$tax = $this->getPayment()->getTax() + 1;
		} else {
			$tax = 1;
		}
		if ($useTax === FALSE && $this->hasPriceWithTax()) {
			foreach ($this->getItems() as $item) {
				$price = $item->getPrice() - ($item->getPrice() / ($this->getPayment()->getTax() + 1.0)) * $this->getPayment()->getTax();
				$total += $price * $item->getCount();
			}
		} else {
			foreach ($this->getItems() as $item) {
				$total += $item->getPrice() * $item->getCount();
			}
		}

		return $total * $tax;
	}
	
}
