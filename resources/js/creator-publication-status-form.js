export const creatorPublicationStatusForm = (initialStatus, defaultPublicationTime) => ({
    status: initialStatus,
    publicationTimeAnnouncement: '',

    statusChanged() {
        if (this.status !== 'published' || !this.$refs.publishedAt || this.$refs.publishedAt.value) {
            return;
        }

        this.$refs.publishedAt.value = defaultPublicationTime;
        this.publicationTimeAnnouncement = 'Publication date set to current time';
    },
});
