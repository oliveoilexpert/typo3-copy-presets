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
		const newContentButtons = document.querySelectorAll('typo3-backend-new-content-element-wizard-button');
		newContentButtons.forEach(button => {
			this.createButton(button);
		});
	}

	createButton(newContentButton) {
		if (newContentButton.nextElementSibling?.classList.contains('copy-preset-button')) {
			return;
		}
		const params = this.extractParameters(newContentButton);
		if (!params) {
			return;
		}
		const buttonTemplate = document.getElementById('tx-copy-presets-paste-button-template');
		if (!buttonTemplate) {
			console.error('Button template not found');
			return;
		}
		const button = buttonTemplate.content.firstElementChild.cloneNode(true);
		const url = `${this.baseWizardUrl}&colPos=${params.colPos}&uid_pid=${params.uidPid}&sys_language_uid=${params.languageId || 0}`;
		button.setAttribute('url', url);
		newContentButton.parentNode.insertBefore(button, newContentButton.nextSibling);
	}

	extractParameters(button) {
		// Get URL from the button's url attribute
		const urlAttr = button.getAttribute('url');
		if (!urlAttr) {
			console.log('No url attribute found on button');
			return null;
		}
		try {
			const url = new URL(urlAttr, window.location.origin);
			const params = new URLSearchParams(url.search);
			const id = params.get('id');
			const colPos = params.get('colPos');
			const uidPid = params.get('uid_pid');
			const languageId = params.get('sys_language_uid');
			const returnUrl = params.get('returnUrl');
			if (id) {
				return {id, colPos, uidPid, languageId, returnUrl}
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