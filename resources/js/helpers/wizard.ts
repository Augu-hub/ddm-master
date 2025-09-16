import Tab from 'bootstrap/js/dist/tab';
export class Wizard {
    private wizard: HTMLElement;
    private validate: boolean;
    private buttons: boolean;
    private progress: boolean;
    private selectedIndex: number;
    private progressBar: HTMLElement | null;
    private navItems: NodeListOf<Element>;
    private tabPans: NodeListOf<Element>;
    private prevBtn: HTMLElement | null;
    private nextBtn: HTMLElement | null;
    private firstBtn: HTMLElement | null;
    private lastBtn: HTMLElement | null;
    constructor(
        element: HTMLElement | string,
        options: {
            validate?: boolean;
            buttons?: boolean;
            progress?: boolean;
        } = {},
    ) {
        if (element instanceof HTMLElement) {
            this.wizard = element;
        } else {
            const wizardElement = document.querySelector<HTMLElement>(element);
            if (!wizardElement) {
                throw new Error('Element not found');
            }
            this.wizard = wizardElement;
        }
        this.validate = options.validate ?? false;
        this.buttons = options.buttons ?? false;
        this.progress = options.progress ?? false;
        this.selectedIndex = 0;
        this.progressBar = null;
        this.navItems = document.querySelectorAll('ul li.nav-item a'); // Temporarily initialized
        this.tabPans = document.querySelectorAll('.tab-content .tab-pane'); // Temporarily initialized
        this.prevBtn = null;
        this.nextBtn = null;
        this.firstBtn = null;
        this.lastBtn = null;
        this.initOptions();
        this.initEventListener();
    }
    // Init Options
    private initOptions(): void {
        this.selectedIndex = 0;
        this.progressBar = this.progress ? this.wizard.querySelector('.tab-content .progress .progress-bar') : null;
        this.navItems = this.wizard.querySelectorAll('ul li.nav-item a');
        this.tabPans = this.wizard.querySelectorAll('.tab-content .tab-pane');
        this.initButtons();
        // Show first selected tab
        this.showTabSelectedTab();
    }
    // Init Buttons
    private initButtons(): void {
        if (this.buttons) {
            this.prevBtn = this.wizard.querySelector('.tab-content .button-previous');
            this.nextBtn = this.wizard.querySelector('.tab-content .button-next');
            this.firstBtn = this.wizard.querySelector('.tab-content .button-first');
            this.lastBtn = this.wizard.querySelector('.tab-content .button-last');
        } else {
            this.prevBtn = this.wizard.querySelector('.tab-content .previous a');
            this.nextBtn = this.wizard.querySelector('.tab-content .next a');
            this.firstBtn = this.wizard.querySelector('.tab-content .first a');
            this.lastBtn = this.wizard.querySelector('.tab-content .last a');
        }
    }
    // Init all button event listeners
    private initEventListener(): void {
        if (this.prevBtn) {
            this.prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (this.selectedIndex > 0 && this.validateForm()) {
                    this.selectedIndex--;
                    this.showTabSelectedTab();
                }
            });
        }
        if (this.nextBtn) {
            this.nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (this.selectedIndex < this.navItems.length - 1 && this.validateForm()) {
                    this.selectedIndex++;
                    this.showTabSelectedTab();
                }
            });
        }
        if (this.firstBtn) {
            this.firstBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (this.selectedIndex !== 0 && this.validateForm()) {
                    this.selectedIndex = 0;
                    this.showTabSelectedTab();
                }
            });
        }
        if (this.lastBtn) {
            this.lastBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (this.selectedIndex !== this.navItems.length - 1 && this.validateForm()) {
                    this.selectedIndex = this.navItems.length - 1;
                    this.showTabSelectedTab();
                }
            });
        }
        this.navItems.forEach((element, index) => {
            element.addEventListener('click', () => {
                this.selectedIndex = index;
                if (this.validateForm()) {
                    this.showTabSelectedTab();
                }
            });
        });
    }
    // Show tab which is selected
    private showTabSelectedTab(): void {
        new Tab(this.navItems[this.selectedIndex] as HTMLElement).show();
        if (this.progressBar) {
            this.progressBar.style.width = (((this.selectedIndex + 1) / this.navItems.length) * 100).toString() + '%';
        }
        this.changeBtnStyle();
    }
    // Change button style enable to disable and vice-versa
    private changeBtnStyle(): void {
        if (this.lastBtn) this.lastBtn.classList.remove('disabled');
        if (this.firstBtn) this.firstBtn.classList.remove('disabled');
        if (this.nextBtn) this.nextBtn.classList.remove('disabled');
        if (this.prevBtn) this.prevBtn.classList.remove('disabled');
        if (this.selectedIndex === 0) {
            if (this.prevBtn) this.prevBtn.classList.add('disabled');
            if (this.firstBtn) this.firstBtn.classList.add('disabled');
        } else if (this.selectedIndex === this.navItems.length - 1) {
            if (this.nextBtn) this.nextBtn.classList.add('disabled');
            if (this.lastBtn) this.lastBtn.classList.add('disabled');
        }
    }
    // If form validate is true then validate a form
    private validateForm(): boolean {
        if (this.validate) {
            const form = this.tabPans[this.selectedIndex].querySelector('form');
            if (form) {
                form.classList.add('was-validated');
                return form.checkValidity();
            }
        }
        return true;
    }
}
export default Wizard;
