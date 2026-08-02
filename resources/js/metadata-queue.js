export const metadataQueue = (subjects = [], filteredChannelId = null) => ({
    selected: [],
    operation: '',
    value: '',
    subjectSearch: '',
    subjects,
    filteredChannelId,

    get pageCheckboxes() {
        return Array.from(this.$root.querySelectorAll('[data-video-select]'));
    },

    get allPageSelected() {
        return this.pageCheckboxes.length > 0 && this.pageCheckboxes.every((checkbox) => this.selected.includes(checkbox.value));
    },

    get selectedChannelIds() {
        return [...new Set(this.pageCheckboxes
            .filter((checkbox) => this.selected.includes(checkbox.value))
            .map((checkbox) => Number(checkbox.dataset.channelId)))];
    },

    get subjectAction() {
        return ['assign_subject', 'assign_primary_subject'].includes(this.operation);
    },

    get requiresValue() {
        return this.operation !== '' && !this.operation.startsWith('review_');
    },

    get mixedSubjectChannels() {
        return this.subjectAction && this.selectedChannelIds.length > 1;
    },

    get filteredSubjects() {
        const channelId = this.selectedChannelIds.length === 1 ? this.selectedChannelIds[0] : Number(this.filteredChannelId);
        if (!channelId || this.mixedSubjectChannels) return [];

        const term = this.subjectSearch.trim().toLocaleLowerCase();
        return this.subjects.filter((subject) => Number(subject.channel_id) === channelId && (!term || subject.name.toLocaleLowerCase().includes(term)));
    },

    toggleAll(checked) {
        this.selected = checked ? this.pageCheckboxes.map((checkbox) => checkbox.value) : [];
        this.value = '';
    },

    clearSelection() {
        this.selected = [];
        this.value = '';
    },

    actionChanged() {
        this.value = '';
        this.subjectSearch = '';
    },
});
