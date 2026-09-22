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

export interface Participant {
    id: string;
    first_name: string;
    last_name: string;
    gender: ParticipantGender;
    team: string;
    birthdate: string;
    age: number;
    classification_id: string;
    paid: boolean;
    classification?: {
        id: string;
        name: string;
    };
}

export interface HeatLane {
    id: string;
    lane_number: number;
    participant_id: string | null;
    finish_time_hundredths: number | null;
    finish_time: string | null;
    participant?: Participant | null;
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
        eligibilities: EventEligibility[];
    };
    lanes: HeatLane[];
}

export interface ResultEventOption {
    id: string;
    label: string;
}

export interface EventResult {
    id: string;
    name: string;
    gender: EventGender;
    label: string;
}

export interface EventResultEntry {
    id: string;
    heat_number: number;
    lane_number: number;
    finish_time_hundredths: number | null;
    finish_time: string | null;
    participant: Participant;
}

export interface CompetitionEvent {
    id: string;
    name: string;
    gender: EventGender;
    sort_order: number;
    eligibilities: EventEligibility[];
    participants?: Participant[];
    participants_count?: number;
    heats?: Heat[];
}

export interface EventShowCompetition {
    id: string;
    name: string;
    number_of_lane: number;
    is_close: boolean;
    participants: Participant[];
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
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    flash: {
        status: string | null;
        import_summary: ImportSummary | null;
    };
};
