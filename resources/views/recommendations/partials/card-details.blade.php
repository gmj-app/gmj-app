<x-recommendation-card
    :recommendation="$recommendation"
    :creator="$creator"
    :usage="$usage"
    :top-requested="$recommendation->id === $topRequestedId"
    :owns-creator="$ownsCreator"
    :anchor="false"
/>
