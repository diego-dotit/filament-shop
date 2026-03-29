import { describe, it, expect } from "vitest";
import { cn } from "../lib/utils";

describe("cn() utility (functional)", () => {
    it("merges two simple class strings", () => {
        expect(cn("flex", "items-center")).toBe("flex items-center");
    });

    it("deduplicates conflicting tailwind padding classes (tailwind-merge)", () => {
        expect(cn("p-4", "p-8")).toBe("p-8");
    });

    it("deduplicates conflicting tailwind text-color classes", () => {
        expect(cn("text-red-500", "text-blue-700")).toBe("text-blue-700");
    });

    it("filters out falsy values via clsx", () => {
        expect(cn("flex", undefined, null, false, "gap-4")).toBe("flex gap-4");
    });

    it("handles conditional object syntax from clsx", () => {
        expect(cn({ "font-bold": true, italic: false }, "underline")).toBe(
            "font-bold underline"
        );
    });

    it("returns empty string when no classes provided", () => {
        expect(cn()).toBe("");
    });
});
