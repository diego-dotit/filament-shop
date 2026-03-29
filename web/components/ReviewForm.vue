<template>
    <!-- Already reviewed: show message instead of form -->
    <div
        v-if="isAuthenticated && alreadyReviewed"
        class="rounded-md bg-muted px-4 py-3 text-sm text-muted-foreground"
        data-testid="already-reviewed"
    >
        <p>You have already submitted a review for this product.</p>
    </div>

    <!-- Success state: show confirmation, hide form -->
    <div
        v-else-if="submitted"
        class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700"
        role="alert"
        data-testid="review-success"
    >
        <p>Review submitted — awaiting moderation. Thank you!</p>
    </div>

    <!-- Form: only for authenticated users who have not yet reviewed -->
    <form
        v-else-if="isAuthenticated"
        class="flex flex-col gap-4"
        data-testid="review-form"
        @submit.prevent="handleSubmit"
    >
        <h3 class="text-lg font-semibold">Write a Review</h3>

        <!-- Star Rating -->
        <div class="flex flex-col gap-2" role="group" aria-label="Rating">
            <label class="font-medium">Rating <span class="text-red-500">*</span></label>
            <div class="flex gap-1">
                <Button
                    v-for="star in 5"
                    :key="star"
                    type="button"
                    variant="ghost"
                    size="sm"
                    :data-testid="`star-${star}`"
                    :aria-label="`Rate ${star} out of 5`"
                    :class="{ 'text-yellow-400': rating >= star }"
                    @click="rating = star"
                >
                    {{ rating >= star ? "★" : "☆" }}
                </Button>
            </div>
            <p v-if="ratingError" class="text-sm text-red-500" role="alert">{{ ratingError }}</p>
        </div>

        <!-- Comment -->
        <div class="flex flex-col gap-2">
            <label for="review-comment" class="font-medium">Comment (optional)</label>
            <Textarea
                id="review-comment"
                v-model="comment"
                :class="{ 'border-red-500': commentTooLong }"
                rows="4"
                maxlength="500"
                placeholder="Share your experience..."
            />
            <p
                class="text-xs mt-1"
                :class="{ 'text-red-500': commentTooLong, 'text-gray-500': !commentTooLong }"
            >
                {{ comment.length }} / 500
            </p>
            <p v-if="commentTooLong" class="text-sm text-red-500" role="alert">
                Comment must not exceed 500 characters.
            </p>
        </div>

        <!-- Submission error -->
        <p v-if="submitError" class="text-sm text-red-500" role="alert">
            {{ submitError }}
        </p>

        <!-- Submit button -->
        <Button
            data-testid="submit-review"
            type="button"
            :disabled="submitting"
            @click="handleSubmit"
        >
            {{ submitting ? "Submitting..." : "Submit Review" }}
        </Button>
    </form>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";

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
