                <el-form-item label="<%label%>"<%required%>>
                    <el-date-picker
                        v-model="form.<%prop%>"
                        type="date"
                        value-format="YYYY-MM-DD"
                        placeholder="选择日期"
                        class="!w-full"
                    />
                </el-form-item>
