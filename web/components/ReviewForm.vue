<template>
    <!-- Already reviewed: show message instead of form -->
    <div
        v-if="isAuthenticated && alreadyReviewed"
        class="review-form__already-reviewed"
        data-testid="already-reviewed"
    >
        <p>You have already submitted a review for this product.</p>
    </div>

    <!-- Success state: show confirmation, hide form -->
    <div
        v-else-if="submitted"
        class="review-form__success"
        role="alert"
        data-testid="review-success"
    >
        <p>Review submitted — awaiting moderation. Thank you!</p>
    </div>

    <!-- Form: only for authenticated users who have not yet reviewed -->
    <form
        v-else-if="isAuthenticated"
        class="review-form"
        data-testid="review-form"
        @submit.prevent="handleSubmit"
    >
        <h3 class="review-form__title">Write a Review</h3>

        <!-- Star Rating -->
        <div class="review-form__rating" role="group" aria-label="Rating">
            <label class="review-form__label"
                >Rating <span class="review-form__required">*</span></label
            >
            <div class="review-form__stars">
                <button
                    v-for="star in 5"
                    :key="star"
                    type="button"
                    :data-testid="`star-${star}`"
                    :aria-label="`Rate ${star} out of 5`"
                    class="review-form__star"
                    :class="{ 'review-form__star--active': rating >= star }"
                    @click="rating = star"
                >
                    {{ rating >= star ? "★" : "☆" }}
                </button>
            </div>
            <p v-if="ratingError" class="review-form__error" role="alert">{{ ratingError }}</p>
        </div>

        <!-- Comment -->
        <div class="review-form__comment-group">
            <label for="review-comment" class="review-form__label">Comment (optional)</label>
            <textarea
                id="review-comment"
                v-model="comment"
                class="review-form__textarea"
                :class="{ 'review-form__textarea--error': commentTooLong }"
                rows="4"
                maxlength="501"
                placeholder="Share your experience..."
            />
            <p
                class="review-form__char-count"
                :class="{ 'review-form__char-count--over': commentTooLong }"
            >
                {{ comment.length }} / 500
            </p>
            <p v-if="commentTooLong" class="review-form__error" role="alert">
                Comment must not exceed 500 characters.
            </p>
        </div>

        <!-- Submission error -->
        <p v-if="submitError" class="review-form__error review-form__error--submit" role="alert">
            {{ submitError }}
        </p>

        <!-- Submit button -->
        <button
            data-testid="submit-review"
            type="button"
            class="review-form__submit"
            :disabled="submitting"
            @click="handleSubmit"
        >
            <span v-if="submitting">Submitting...</span>
            <span v-else>Submit Review</span>
        </button>
    </form>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";

// ---------------------------------------------------------------------------
// Props
// ---------------------------------------------------------------------------

const props = withDefaults(
    defineProps<{
        productId: number;
        alreadyReviewed?: boolean;
    }>(),
    {
        alreadyReviewed: false,
    }
);

// ---------------------------------------------------------------------------
// Composables
// ---------------------------------------------------------------------------

const { isAuthenticated } = useAuth();
const api = useApi();

// ---------------------------------------------------------------------------
// State
// ---------------------------------------------------------------------------

const rating = ref<number>(0);
const comment = ref<string>("");
const submitting = ref(false);
const submitted = ref(false);
const ratingError = ref<string | null>(null);
const submitError = ref<string | null>(null);

// ---------------------------------------------------------------------------
// Derived
// ---------------------------------------------------------------------------

const commentTooLong = computed(() => comment.value.length > 500);

// ---------------------------------------------------------------------------
// Actions
// ---------------------------------------------------------------------------

async function handleSubmit() {
    // Reset errors
    ratingError.value = null;
    submitError.value = null;

    // Validate rating
    if (!rating.value || rating.value < 1) {
        ratingError.value = "A rating is required";
        return;
    }

    // Validate comment length
    if (commentTooLong.value) {
        return;
    }

    submitting.value = true;

    try {
        await api(`/products/${props.productId}/reviews`, {
            method: "POST",
            body: {
                rating: rating.value,
                comment: comment.value,
            },
        });

        // Clear form and show success
        rating.value = 0;
        comment.value = "";
        submitted.value = true;
    } catch (error: unknown) {
        const err = error as {
            status?: number;
            statusCode?: number;
            response?: { status?: number };
        };
        const status = err?.status ?? err?.statusCode ?? err?.response?.status;

        if (status === 409) {
            submitError.value = "You have already reviewed this product.";
        } else {
            submitError.value = "Failed to submit your review. Please try again.";
        }
    } finally {
        submitting.value = false;
    }
}
</script>
