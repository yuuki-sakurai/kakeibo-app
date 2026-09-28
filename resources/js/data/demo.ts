import type {
    BankAccount,
    ImportHistory,
    NavItem,
    Transaction,
} from "../types";

export const dashboardDate = new Date(2026, 7, 25);

export const navigationItems: NavItem[] = [
    { key: "home", label: "ホーム", icon: "⌂" },
    { key: "transactions", label: "利用明細", icon: "≡" },
    { key: "imports", label: "取込履歴", icon: "↥" },
    { key: "cards", label: "カード設定", icon: "▣" },
];

export const transactions: Transaction[] = [
    {
        date: "2026/08/08",
        merchant: "Amazon.co.jp",
        category: "ショッピング",
        amount: 4820,
        paymentMonth: "2026年9月",
    },
    {
        date: "2026/08/06",
        merchant: "スターバックス コーヒー",
        category: "飲食",
        amount: 680,
        paymentMonth: "2026年9月",
    },
    {
        date: "2026/08/03",
        merchant: "JR東日本 モバイルSuica",
        category: "交通",
        amount: 3000,
        paymentMonth: "2026年9月",
    },
    {
        date: "2026/08/01",
        merchant: "東京電力 電気料金",
        category: "光熱費",
        amount: 8740,
        paymentMonth: "2026年9月",
    },
    {
        date: "2026/07/29",
        merchant: "無印良品",
        category: "ショッピング",
        amount: 6290,
        paymentMonth: "2026年8月",
    },
    {
        date: "2026/07/24",
        merchant: "Netflix.com",
        category: "サブスク",
        amount: 1490,
        paymentMonth: "2026年8月",
    },
];

transactions.push(
    {
        date: "2026/08/10",
        merchant: "イトーヨーカドー",
        category: "食費",
        amount: 5680,
        paymentMonth: "2026年9月",
    },
    {
        date: "2026/08/12",
        merchant: "マツモトキヨシ",
        category: "日用品",
        amount: 2340,
        paymentMonth: "2026年9月",
    },
    {
        date: "2026/08/12",
        merchant: "ランチ",
        category: "飲食",
        amount: 1200,
        paymentMonth: "2026年9月",
    },
    {
        date: "2026/08/15",
        merchant: "ユニクロ",
        category: "衣服",
        amount: 7990,
        paymentMonth: "2026年9月",
    },
    {
        date: "2026/08/18",
        merchant: "成城石井",
        category: "食費",
        amount: 4260,
        paymentMonth: "2026年9月",
    },
    {
        date: "2026/08/21",
        merchant: "Netflix.com",
        category: "サブスク",
        amount: 1490,
        paymentMonth: "2026年9月",
    },
    {
        date: "2026/08/23",
        merchant: "ENEOS",
        category: "交通",
        amount: 6000,
        paymentMonth: "2026年9月",
    },
    {
        date: "2026/08/25",
        merchant: "セブン-イレブン",
        category: "食費",
        amount: 980,
        paymentMonth: "2026年9月",
    },
);

export const bankAccounts: BankAccount[] = [
    {
        id: 1,
        bankName: "三菱UFJ銀行",
        branchName: "渋谷支店",
        accountType: "普通",
        balance: 286450,
        color: "#d84b58",
    },
    {
        id: 2,
        bankName: "楽天銀行",
        branchName: "サルサ支店",
        accountType: "普通",
        balance: 142800,
        color: "#9a184d",
    },
    {
        id: 3,
        bankName: "住信SBIネット銀行",
        branchName: "イチゴ支店",
        accountType: "普通",
        balance: 93520,
        color: "#178c9b",
    },
];

export const importHistories: ImportHistory[] = [
    {
        fileName: "epos_202608.csv",
        importedAt: "2026/08/09 08:42",
        importedCount: 12,
        excludedCount: 0,
        hasWarning: false,
    },
    {
        fileName: "epos_202607.csv",
        importedAt: "2026/07/31 21:18",
        importedCount: 24,
        excludedCount: 2,
        hasWarning: true,
    },
    {
        fileName: "epos_202606.csv",
        importedAt: "2026/06/30 19:06",
        importedCount: 18,
        excludedCount: 0,
        hasWarning: false,
    },
];
