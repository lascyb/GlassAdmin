                <el-form-item label="<%label%>"<%required%>>
                    <el-date-picker
                        v-model="form.<%prop%>"
                        type="datetime"
                        value-format="X"
                        placeholder="选择日期时间"
                        class="!w-full"
                    />
                </el-form-item>
