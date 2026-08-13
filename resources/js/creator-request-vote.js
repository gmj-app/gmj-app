const normalizedVoteCount = (value) => Math.max(0, Number.parseInt(value, 10) || 0);

const validVotePayload = (payload) => payload
    && typeof payload.has_voted === 'boolean'
    && Number.isInteger(Number(payload.votes))
    && Number(payload.votes) >= 0;

export const creatorRequestVote = ({
    requestId,
    requestTitle,
    detailsUrl,
    voteUrl,
    loginUrl = null,
    authenticated = false,
    votable = false,
    hasVoted = false,
    votes = 0,
    csrfToken = '',
    request = globalThis.fetch?.bind(globalThis),
    navigate = (url) => globalThis.location?.assign(url),
} = {}) => ({
    requestId: Number(requestId),
    requestTitle: String(requestTitle ?? 'Request'),
    detailsUrl,
    voteUrl,
    loginUrl,
    authenticated: Boolean(authenticated),
    votable: Boolean(votable),
    hasVoted: Boolean(hasVoted),
    votes: normalizedVoteCount(votes),
    csrfToken,
    votePending: false,
    voteError: false,
    voteAnnouncement: '',
    alternativeOpen: false,
    withdrawOpen: false,
    loaded: false,
    loading: false,
    error: false,
    detailsHtml: '',
    get open() {
        return this.expandedRequestId === this.requestId;
    },
    get voteLabel() {
        return this.hasVoted
            ? `Remove vote from “${this.requestTitle}”`
            : `Vote for “${this.requestTitle}”`;
    },
    async loadDetails() {
        if (this.loaded || this.loading) return;

        this.loading = true;
        this.error = false;

        try {
            const response = await request(this.detailsUrl, {
                headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error(`Request failed: ${response.status}`);

            this.detailsHtml = await response.text();
            this.loaded = true;
        } catch (error) {
            this.error = true;
        } finally {
            this.loading = false;
        }
    },
    toggleDetails() {
        this.toggleRequest(this.requestId);
    },
    async toggleVote(event, source = 'collapsed_blade') {
        event?.preventDefault?.();
        event?.stopPropagation?.();

        if (!this.votable || this.votePending) return;

        if (!this.authenticated) {
            if (this.loginUrl) navigate(this.loginUrl);
            return;
        }

        const previous = { hasVoted: this.hasVoted, votes: this.votes };
        const adding = !this.hasVoted;

        this.votePending = true;
        this.voteError = false;
        this.voteAnnouncement = '';
        this.hasVoted = adding;
        this.votes = Math.max(0, this.votes + (adding ? 1 : -1));

        try {
            const response = await request(this.voteUrl, {
                method: adding ? 'POST' : 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if ([401, 419].includes(response.status) && this.loginUrl) {
                this.hasVoted = previous.hasVoted;
                this.votes = previous.votes;
                navigate(this.loginUrl);
                return;
            }

            if (!response.ok) throw new Error(`Vote request failed: ${response.status}`);

            const payload = await response.json();
            if (!validVotePayload(payload)) throw new Error('Vote response was invalid.');

            this.hasVoted = payload.has_voted;
            this.votes = normalizedVoteCount(payload.votes);
            this.voteAnnouncement = `${this.hasVoted ? 'Vote added.' : 'Vote removed.'} ${this.votes} ${this.votes === 1 ? 'vote' : 'votes'}.`;

            globalThis.dispatchEvent?.(new CustomEvent('gmj:vote-updated', {
                detail: {
                    request_id: this.requestId,
                    has_voted: this.hasVoted,
                    votes: this.votes,
                    source,
                },
            }));
        } catch (error) {
            this.hasVoted = previous.hasVoted;
            this.votes = previous.votes;
            this.voteError = true;
            this.voteAnnouncement = 'We couldn’t update your vote. Try again.';
        } finally {
            this.votePending = false;
        }
    },
});

export { normalizedVoteCount, validVotePayload };
