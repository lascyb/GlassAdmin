                <el-form-item label="<%label%>"<%required%>>
                    <el-date-picker
                        v-model="form.<%prop%>"
                        type="datetime"
                        value-format="<%valueFormat%>"
                        placeholder="<%placeholder%>"
                        class="w-full!"
                    />
                </el-form-item>
