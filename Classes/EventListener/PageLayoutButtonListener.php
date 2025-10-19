<?php

declare(strict_types=1);

namespace UBOS\CopyPresets\EventListener;

use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use UBOS\CopyPresets\Service\CopyPresetService;

class PageLayoutButtonListener
{
	public function __construct(
		private readonly UriBuilder $uriBuilder,
		private readonly CopyPresetService $copyPresetService,
		private readonly PageRenderer $pageRenderer
	) {}

	public function __invoke(ModifyPageLayoutContentEvent $event): void
	{
		$request = $event->getRequest();
		$pageId = (int)($request->getQueryParams()['id'] ?? 0);

		if ($pageId === 0) {
			return;
		}

		// Check if there are any copy presets available
		$presets = $this->copyPresetService->getCopyPresetPages();
		if (empty($presets)) {
			return;
		}

		// Get the wizard URL
		$wizardUrl = (string)$this->uriBuilder->buildUriFromRoute('copy_preset_wizard', [
			'id' => $pageId,
		]);

		// Pass wizard URL via data attribute on body instead of inline script (CSP-safe)
		$this->pageRenderer->addBodyContent(
			'
			<template id="tx-copy-presets-paste-button-template">
				<typo3-backend-new-content-element-wizard-button 
					url=""
					class="btn btn-default btn-sm copy-preset-button"
					subject="Copy from preset"
					role="button" 
					subject="Copy Preset" 
					tabindex="0" >
								<span class="icon icon-size-small">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">
						<rect x="1" y="1" width="9" height="11" fill="none" stroke="currentColor" stroke-width="1.5" rx="1"/>
						<rect x="6" y="4" width="9" height="11" fill="none" stroke="currentColor" stroke-width="1.5" rx="1"/>
					</svg>
					</span>
					Copy preset
				</typo3-backend-new-content-element-wizard-button>
			</template>
			<script type="application/json" id="tx-copy-presets-config">' .
			json_encode(['wizardUrl' => $wizardUrl]) .
			'</script>'
		);

		// Load the ES6 module
		$this->pageRenderer->loadJavaScriptModule('@ubos/copy-presets/copy-preset-wizard.js');
	}
}