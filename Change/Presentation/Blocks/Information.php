<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Presentation\Blocks;

use Change\Documents\Property;

/**
 * @name \Change\Presentation\Blocks\Information
 */
class Information
{
	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $section;

	/**
	 * @var string
	 */
	protected $label;

	/**
	 * @var boolean
	 */
	protected $mailSuitable;

	/**
	 * @var ParameterInformation[]
	 */
	protected $parametersInformation = array();

	/**
	 * @param string $name
	 */
	function __construct($name)
	{
		$this->name = $name;
		list($vendor, $shortModuleName,) = explode('_', $name);
		$this->section = $vendor . '_' . $shortModuleName;
	}

	/**
	 * @api
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{

	}

	/**
	 * @param string $section
	 * @return $this
	 */
	public function setSection($section)
	{
		$this->section = $section;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getSection()
	{
		return $this->section;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->label ? $this->label : $this->getName();
	}

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setLabel($label)
	{
		$this->label = $label;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function isMailSuitable()
	{
		return $this->mailSuitable !== null ? $this->mailSuitable : false;
	}

	/**
	 * @param boolean $mailSuitable
	 */
	public function setMailSuitable($mailSuitable)
	{
		$this->mailSuitable = $mailSuitable;
	}

	/**
	 * @param string $name
	 * @param string $type
	 * @param bool $required
	 * @param mixed $defaultValue
	 * @return ParameterInformation
	 */
	public function addInformationMeta($name, $type = Property::TYPE_STRING, $required = false, $defaultValue = null)
	{
		$parameterInformation = new ParameterInformation($name, $type, $required, $defaultValue);
		$key = $this->ucLower($name);
		$this->parametersInformation[$key] = $parameterInformation;
		return $parameterInformation;
	}

	/**
	 * @param string|string[] $allowedModelsNames
	 * @param \Change\I18n\I18nManager $i18nManager
	 * @return ParameterInformation
	 */
	public function addInformationMetaForDetailBlock($allowedModelsNames, $i18nManager)
	{
		return $this->addInformationMeta(\Change\Presentation\Blocks\Standard\Block::DOCUMENT_TO_DISPLAY_PROPERTY_NAME, Property::TYPE_DOCUMENTID, false, null)
			->setLabel($i18nManager->trans('m.rbs.website.admin.block_property_document_to_display', array('ucf')))
			->setAllowedModelsNames($allowedModelsNames);
	}

	/**
	 * @param integer $defaultValue
	 * @return ParameterInformation
	 */
	protected function addTTL($defaultValue)
	{
		$parameterInformation = $this->addInformationMeta('TTL', Property::TYPE_INTEGER, true, $defaultValue);
		return $parameterInformation;
	}

	/**
	 * @return ParameterInformation[]
	 */
	public function getParametersInformation()
	{
		return array_values($this->parametersInformation);
	}

	/**
	 * @param string $name
	 * @return boolean
	 */
	public function hasParameterInformation($name)
	{
		$key = $this->ucLower($name);
		return isset($this->parametersInformation[$key]);
	}

	/**
	 * @param string $name
	 * @return ParameterInformation|null
	 */
	public function getParameterInformation($name)
	{
		$key = $this->ucLower($name);
		return isset($this->parametersInformation[$key]) ? $this->parametersInformation[$key] : null;
	}

	/**
	 * @param string $name
	 * @return ParameterInformation|null
	 */
	public function removeParameterInformation($name)
	{
		$key = $this->ucLower($name);
		if (isset($this->parametersInformation[$key]))
		{
			$parameter = $this->parametersInformation[$key];
			unset($this->parametersInformation[$key]);
			return $parameter;
		}
		return null;
	}

	/**
	 * @param string $name
	 * @return string
	 */
	protected function ucLower($name)
	{
		return strtolower($name[0]) . substr($name, 1);
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array = array('name' => $this->getName(), 'label' => $this->getLabel());
		$parameters = array();
		foreach($this->getParametersInformation() as $parameterInformation)
		{
			$parameters[] = $parameterInformation->toArray();
		}
		$array['parameters'] = $parameters;
		return $array;
	}
}