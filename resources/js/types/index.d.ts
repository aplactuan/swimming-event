export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
}

export interface AgeBracket {
    id: string;
    name: string;
    start_birthday: string | null;
    end_birthday: string | null;
    sort_order: number;
}

export interface Classification {
    id: string;
    name: string;
    parent_id: string | null;
    sort_order: number;
    inherits_age_brackets: boolean;
    age_brackets: AgeBracket[];
    children: Classification[];
}

export type EventGender = 'male' | 'female' | 'mixed';

export type ProgramSortColumn =
    | 'classification'
    | 'age_bracket'
    | 'gender'
    | 'name';

export type ParticipantGender = 'male' | 'female';

export interface EventEligibility {
    id: string;
    classification_id: string;
    age_bracket_id: string;
    classification?: {
        id: string;
        name: string;
    };
    age_bracket?: {
        id: string;
        name: string;
        start_birthday: string | null;
        end_birthday: string | null;
    };
}

export interface Team {
    id: string;
    name: string;
    participants_count?: number;
}

export interface CompetitionPointRule {
    id: string;
    place: number;
    points: number;
}

export type EventType = 'individual' | 'relay';

export type HeatLaneStatus = 'swam' | 'ns';

export interface RelaySwimmer {
    order: number;
    participant_id?: string;
    participant: {
        id: string;
        first_name: string;
        last_name: string;
    } | null;
}

export interface Participant {
    id: string;
    first_name: string;
    last_name: string;
    gender: ParticipantGender;
    team_id: string;
    team: string | null;
    birthdate: string;
    age: number;
    classification_id: string;
    paid: boolean;
    classification?: {
        id: string;
        name: string;
    };
}

export interface HeatLaneEntryEvent {
    id: string;
    name: string;
    gender: EventGender;
    eligibilities: EventEligibility[];
}

export interface HeatLane {
    id: string;
    lane_number: number;
    participant_id: string | null;
    team_id: string | null;
    entry_event_id: string | null;
    finish_time_hundredths: number | null;
    finish_time: string | null;
    status: HeatLaneStatus;
    participant?: Participant | null;
    team?: { id: string; name: string } | null;
    entry_event?: HeatLaneEntryEvent | null;
    relay_swimmers?: RelaySwimmer[];
}

export interface Heat {
    id: string;
    heat_number: number;
    lanes: HeatLane[];
}

export interface RaceHeatOption {
    id: string;
    label: string;
}

export interface RaceHeat {
    id: string;
    heat_number: number;
    label: string;
    event: {
        id: string;
        name: string;
        gender: EventGender;
        type: EventType;
        eligibilities: EventEligibility[];
    };
    lanes: HeatLane[];
}

export interface ResultEventOptionEligibility {
    classification_id: string;
    classification: string;
    classification_sort_order: number;
    age_bracket_id: string;
    age_bracket: string;
    age_bracket_sort_order: number;
}

export interface ResultEventOption {
    id: string;
    label: string;
    name: string;
    gender: EventGender;
    eligibilities: ResultEventOptionEligibility[];
}

export interface EventResult {
    id: string;
    name: string;
    gender: EventGender;
    type: EventType;
    label: string;
}

export interface EventResultEntry {
    id: string;
    heat_number: number;
    lane_number: number;
    finish_time_hundredths: number | null;
    finish_time: string | null;
    place: number | null;
    points: number | null;
    status: HeatLaneStatus;
    participant: Participant | null;
    team?: { id: string; name: string } | null;
    relay_swimmers?: RelaySwimmer[];
}

export interface StandingMvpRow {
    participant_id: string;
    first_name: string;
    last_name: string;
    team: string | null;
    points: number;
}

export interface StandingMvpGroup {
    classification_id: string;
    classification: string;
    age_bracket_id: string;
    age_bracket: string;
    gender: string;
    standings: StandingMvpRow[];
}

export interface StandingTeamRow {
    team_id: string;
    name: string;
    points: number;
}

export interface CompetitionEvent {
    id: string;
    name: string;
    gender: EventGender;
    type: EventType;
    sort_order: number;
    eligibilities: EventEligibility[];
    participants?: Participant[];
    participants_count?: number;
    teams?: Team[];
    teams_count?: number;
    heats?: Heat[];
}

export interface EventShowCompetition {
    id: string;
    name: string;
    number_of_lane: number;
    is_close: boolean;
    participants: Participant[];
    teams: Team[];
}

export interface Competition {
    id: string;
    name: string;
    venue: string;
    number_of_lane: number;
    competition_date: string;
    warm_up_time: string | null;
    coaches_meeting_time: string | null;
    registration_deadline: string;
    entry_fee: number;
    is_close: boolean;
    classifications?: Classification[];
    events?: CompetitionEvent[];
    participants?: Participant[];
    teams?: Team[];
    point_rules?: CompetitionPointRule[];
}

export interface PaginatedMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

export interface Paginated<T> {
    data: T[];
    meta: PaginatedMeta;
}

export interface ImportSummary {
    imported: number;
    skipped_duplicates: number;
    skipped_invalid: number;
    classifications_created: number;
    teams_created?: number;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User | null;
    };
    flash: {
        status: string | null;
        import_summary: ImportSummary | null;
    };
};
