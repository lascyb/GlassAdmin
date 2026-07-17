import { FIELD_TYPE } from '../constants/mysql-field-type'

/** FieldType MySQL 列数据类型（CREATE TABLE 类型名） */
export type FieldType = (typeof FIELD_TYPE)[keyof typeof FIELD_TYPE]
