                <el-form-item label="<%label%>"<%required%>>
                    <el-date-picker
                        v-model="form.<%prop%>"
                        type="date"
                        value-format="YYYY-MM-DD"
                        placeholder="<%placeholder%>"
                        class="w-full!"
                    />
                </el-form-item>
