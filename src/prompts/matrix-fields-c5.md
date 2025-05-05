# Matrix Fields (Craft 5+)

In Craft 5, Matrix Blocks were replaced by Entries. So a Matrix field contains multiple Entries, each as its own block. Instead of defining block types within the Matrix Field, you define them as separate Entry Types.

- Matrix Blocks -> Entries
- Block Types -> Entry Types

Block Types are now Entry Types, and the fields within them are defined as part of the Entry Type.

## When creating a Matrix Field

In Craft 5, you must ensure that the necessary Entry Types are created **before** creating a Matrix Field.

Before creating a Matrix Field, you MUST **review all existing entry types**. Determine whether you need to create a new entry type, or if you should be reusing an existing one.

## No more field groups

The concept of "field groups" has been removed. All fields now exist in a global space.
