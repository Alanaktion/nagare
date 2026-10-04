export type LabelColor =
    | 'gray'
    | 'red'
    | 'orange'
    | 'amber'
    | 'green'
    | 'teal'
    | 'blue'
    | 'indigo'
    | 'purple'
    | 'pink';

export type Label = {
    id: number;
    name: string;
    color: LabelColor;
    /** Number of issues with the label, on the board settings page. */
    issues_count?: number;
};
