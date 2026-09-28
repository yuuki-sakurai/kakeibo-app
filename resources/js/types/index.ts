export type Page = "home" | "transactions" | "imports" | "cards";

export interface NavItem {
    key: Page;
    label: string;
    icon: string;
}

export interface Transaction {
    date: string;
    merchant: string;
    category: string;
    amount: number;
    paymentMonth: string;
}

export interface ImportHistory {
    fileName: string;
    importedAt: string;
    importedCount: number;
    excludedCount: number;
    hasWarning: boolean;
}

export interface BankAccount {
    id: number;
    bankName: string;
    branchName: string;
    accountType: string;
    balance: number;
    color: string;
}
