const normalizeRequestIds = (requestIds) => [...new Set(
    requestIds
        .map((requestId) => Number(requestId))
        .filter((requestId) => Number.isInteger(requestId) && requestId > 0),
)];

export const visibleRequestIdFromHash = (hash, requestIds) => {
    const match = String(hash ?? '').match(/^#recommendation-(\d+)$/);
    const requestId = match ? Number(match[1]) : null;

    return requestIds.includes(requestId) ? requestId : null;
};

export const creatorRequestAccordion = (requestIds = [], initialRequestId = null) => {
    const visibleRequestIds = normalizeRequestIds(requestIds);
    const normalizedInitialRequestId = Number(initialRequestId);
    const defaultRequestId = visibleRequestIds.includes(normalizedInitialRequestId)
        ? normalizedInitialRequestId
        : null;
    const hashRequestId = visibleRequestIdFromHash(globalThis.location?.hash, visibleRequestIds);

    return {
        requestIds: visibleRequestIds,
        expandedRequestId: hashRequestId ?? defaultRequestId,
        toggleRequest(requestId) {
            const normalizedRequestId = Number(requestId);

            if (!this.requestIds.includes(normalizedRequestId)) return;

            this.expandedRequestId = this.expandedRequestId === normalizedRequestId
                ? null
                : normalizedRequestId;
        },
        openHashRequest(hash = globalThis.location?.hash) {
            const requestId = visibleRequestIdFromHash(hash, this.requestIds);

            if (requestId === null) return false;

            this.expandedRequestId = requestId;

            return true;
        },
    };
};
