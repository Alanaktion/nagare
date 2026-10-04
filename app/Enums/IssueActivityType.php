<?php

namespace App\Enums;

/**
 * The changes to an issue that are recorded in its timeline.
 */
enum IssueActivityType: string
{
    case Created = 'created';
    case Renamed = 'renamed';
    case Moved = 'moved';
    case Closed = 'closed';
    case Reopened = 'reopened';
    case Assigned = 'assigned';
    case SprintChanged = 'sprint_changed';
    case LabelsChanged = 'labels_changed';
}
