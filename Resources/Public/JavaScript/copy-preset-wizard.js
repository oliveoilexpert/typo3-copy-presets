/**
 * Copy Preset Wizard JavaScript Module
 */
console.log('Copy Preset Wizard module loading...');

class CopyPresetWizard {
	constructor() {
		this.initialize();
	}

	initialize() {
		this.baseWizardUrl = this.getWizardBaseUrl();
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', () => {
				this.injectButtons();
			});
		} else {
			this.injectButtons();
		}
		// this.observePageModule();
	}

	injectButtons() {
		const newContentButtons = document.querySelectorAll('typo3-backend-new-content-element-wizard-button, .t3-page-ce-actions.t3js-page-new-ce>a');
		newContentButtons.forEach(button => {
			this.createButton(button);
		});
	}

	createButton(newContentButton) {
		if (newContentButton.nextElementSibling?.classList.contains('copy-preset-button')) {
			return;
		}
		const params = this.extractParameters(newContentButton);
		if (newContentButton.tagName === 'A') {
			console.log(params);
		}
		if (!params) {
			return;
		}
		const buttonTemplate = document.getElementById('tx-copy-presets-paste-button-template');
		if (!buttonTemplate) {
			console.error('Button template not found');
			return;
		}
		const button = buttonTemplate.content.firstElementChild.cloneNode(true);
		const url = `${this.baseWizardUrl}&colPos=${params.colPos}&uid_pid=${params.uidPid}&sys_language_uid=${params.languageId || '0'}${params.txContainerParent ? `&tx_container_parent=${params.txContainerParent}` : ''}`;
		button.setAttribute('url', url);
		newContentButton.parentNode.insertBefore(button, newContentButton.nextSibling);
	}

	extractParameters(button) {
		// Get URL from the button's url attribute
		if (button.tagName === 'A') {
			const hrefAttr = button.getAttribute('href');
			if (!hrefAttr) {
				console.log('ext:copy_presets: No href attribute found on button');
				return null;
			}
			try {
				const params = new URLSearchParams(new URL(hrefAttr, window.location.origin).search);
				let uidPid;
				params.forEach((value, key) => {
					if (value === 'new' && key.startsWith('edit[tt_content][')) {
						uidPid = key.slice('edit[tt_content]['.length, -1);
					}
				})
				return {
					colPos: params.get('defVals[tt_content][colPos]'),
					uidPid,
					languageId: params.get('defVals[tt_content][sys_language_uid]'),
					returnUrl: params.get('returnUrl'),
					txContainerParent: params.get('defVals[tt_content][tx_container_parent]')
				}
			} catch (e) {
				console.error('Failed to parse URL:', e);
			}
			return null;
		}

		const urlAttr = button.getAttribute('url');
		if (!urlAttr) {
			console.log('ext:copy_presets: No href attribute found on button');
			return null;
		}
		try {
			const params = new URLSearchParams(new URL(urlAttr, window.location.origin).search);
			return {
				colPos: params.get('colPos'),
				uidPid: params.get('uid_pid'),
				languageId: params.get('sys_language_uid'),
				returnUrl: params.get('returnUrl'),
				txContainerParent: params.get('tx_container_parent')
			}
		} catch (e) {
			console.error('Failed to parse URL:', e);
		}
		return null;
	}

	getWizardBaseUrl() {
		const configElement = document.getElementById('tx-copy-presets-config');
		if (configElement) {
			try {
				const config = JSON.parse(configElement.textContent);
				return config.wizardUrl;
			} catch (e) {
				console.error('Failed to parse config:', e);
			}
		}
	}

	observePageModule() {
		// Observer for dynamically loaded content in the page module
		const observer = new MutationObserver((mutations) => {
			this.injectButtons();
		});

		observer.observe(document.body, {
			childList: true,
			subtree: true
		});
	}
}

// Initialize when module is loaded
const wizard = new CopyPresetWizard();

export default wizard;